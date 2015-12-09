<?php

namespace DTForce\NetteConsole\DI;

use DTForce\NetteConsole\Exception\ServiceNotFoundException;
use Nette\DI\CompilerExtension;
use Nette\DI\ContainerBuilder;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;


class ConsoleExtension extends CompilerExtension
{
	const HELPER_TAG_NAME = 'helperTagName';

	/**
	 * {@inheritdoc}
	 */
	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$builder->addDefinition(self::scrambledServiceName($builder, Application::class))
			->setClass(Application::class);

		$builder->addDefinition(self::scrambledServiceName($builder, HelperSet::class))
			->setClass(HelperSet::class);

		$builder->addDefinition(self::scrambledServiceName($builder, QuestionHelper::class))
			->setClass(QuestionHelper::class);
	}


	/**
	 * {@inheritdoc}
	 */
	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();

		$helperSetName = self::getDefinitionNameByTypeChecked($builder, HelperSet::class);
		$applicationDefinition = self::getDefinitionByTypeChecked($builder, Application::class);
		$applicationDefinition->addSetup('setHelperSet', ['@' . $helperSetName]);

		foreach ($builder->findByType(Command::class) as $name => $commandDefinition) {
			$applicationDefinition->addSetup('add', ['@' . $name]);
		}

		$helperSetDefinition = $builder->getDefinition($helperSetName);
		foreach ($builder->findByType(HelperInterface::class) as $helperDefinition) {
			$helperSetDefinition->addSetup(
				'set',
				[
					'@' . $helperDefinition->getClass(),
					$helperDefinition->getTag(self::HELPER_TAG_NAME)
				]
			);
		}
	}


	/**
	 * @param ContainerBuilder $builder
	 * @param string $type
	 * @return string
	 * @throws ServiceNotFoundException
	 */
	private static function getDefinitionNameByTypeChecked(ContainerBuilder $builder, $type)
	{
		$defName = $builder->getByType($type);
		if ($defName === null) {
			throw new ServiceNotFoundException();
		}
		return $defName;
	}


	/**
	 * @param ContainerBuilder $builder
	 * @param string $type
	 * @return \Nette\DI\ServiceDefinition
	 * @throws ServiceNotFoundException
	 */
	private static function getDefinitionByTypeChecked(ContainerBuilder $builder, $type)
	{
		return $builder->getDefinition(self::getDefinitionNameByTypeChecked($builder, $type));
	}


	/**
	 * @param ContainerBuilder $builder
	 * @param string $className
	 * @return string
	 */
	private static function scrambledServiceName(ContainerBuilder $builder, $className)
	{
		return (count($builder->getDefinitions()) + 1) . preg_replace('#\W+#', '_', $className);
	}

}

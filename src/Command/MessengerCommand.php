<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command;

use Nette\PhpGenerator\ClassType;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use WebChemistry\Generette\Command\Argument\MessengerArguments;
use WebChemistry\Generette\Property\PropertiesOption;
use WebChemistry\Generette\Utility\FilePath;
use WebChemistry\Generette\Utility\UseStatements;
use WebChemistry\ServiceAttribute\Attribute\Service;

final class MessengerCommand extends GenerateCommand
{

	public static $defaultName = 'make:messenger';

	protected MessengerArguments $arguments;

	private PropertiesOption $propertiesOption;

	private PropertiesOption $injectOption;

	public function __construct(
		private string $namespace,
		private string $interface = MessageHandlerInterface::class,
	)
	{
		parent::__construct();
	}

	protected function configure(): void
	{
		parent::configure();

		$this->propertiesOption = $this->createPropertiesOption(shortcut: 'p')
			->setConstructorFlag(true)
			->initialize();

		$this->injectOption = $this->createPropertiesOption('inject', description: 'Generate handler inject constructor')
			->setConstructorFlag(true)
			->setPromotedFlag(true)
			->initialize();
	}

	protected function exec(): void
	{
		$writer = $this->createFilesWriter();
		$className = $this->createClassNameFromArguments($this->arguments, $this->namespace);
		$handlerClassName = $className->withAppendedClassName('Handler');

		// message file
		$class = $this->createClassFromClassName($file = $this->createPhpFile(), $className);
		$this->processMessageClass($class);

		$this->propertiesOption->generateAll($this->useStatements, $class);

		$writer->addFile($this->getFilePathFromClassName($className), $this->printer->printFile($file));

		// handler file
		$class = $this->createClassFromClassName($file = $this->createPhpFile(), $handlerClassName);
		$this->processHandlerClass($class, $className->getFullName());

		$this->injectOption->setUseStatements($this->useStatements)
			->generateConstructor($class->getMethod('__construct'));

		$writer->addFile($this->getFilePathFromClassName($handlerClassName), $this->printer->printFile($file));

		// directories
		$writer->write();
	}

	private function processMessageClass(ClassType $class): void
	{
		$class->setFinal();
	}

	private function processHandlerClass(ClassType $class, string $parameterClassName): void
	{
		$class->addImplement($this->useStatements->use($this->interface));
		$class->setFinal();

		if (class_exists(Service::class)) {
			$class->addAttribute($this->useStatements->use(Service::class));
		}

		$class->addMethod('__construct');

		$invoke = $class->addMethod('__invoke');
		$invoke->setReturnType('void');
		$invoke->addParameter('data')
			->setType($this->useStatements->use($parameterClassName));
	}
}

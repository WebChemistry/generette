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
		private string $basePath,
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
			->setGetterFlag(true)
			->initialize();
	}

	protected function exec(): void
	{
		$baseClassName = $this->createClassName($this->arguments->name);
		$className = $baseClassName->withPrependedNamespace($this->namespace);
		$handlerClassName = $className->withAppendedClassName('Handler', true);

		// message file
		$file = $this->createPhpFile();
		$namespace = $file->addNamespace($className->getNamespace());
		$this->useStatements = new UseStatements($namespace);
		$this->processMessageClass($class = $namespace->addClass($className->getClassName()));
		$constructor = $class->addMethod('__construct');

		$this->propertiesOption->setUseStatements($this->useStatements)
			->generateConstructor($constructor)
			->generateProperties($class)
			->generateGettersAndSetters($class);

		// handler file
		$handlerFile = $this->createPhpFile();
		$namespace = $handlerFile->addNamespace($handlerClassName->getNamespace());
		$this->useStatements = new UseStatements($namespace);
		$this->processHandlerClass($class = $namespace->addClass($handlerClassName->getClassName()), $className->getFullName());

		$this->injectOption->setUseStatements($this->useStatements)
			->generateConstructor($class->getMethod('__construct'));

		// directories
		$baseDir = new FilePath($this->basePath, $baseClassName->getPath());

		$this->createFilesWriter()
			->addFile(
				$baseDir->withAppendedPath($className->getFileName())->toString(),
				$this->printer->printFile($file),
			)
			->addFile(
				$baseDir->withAppendedPath($handlerClassName->getFileName())->toString(),
				$this->printer->printFile($handlerFile),
			)
			->write();
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

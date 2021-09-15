<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command;

use Nette\PhpGenerator\ClassType;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use WebChemistry\Generette\Utility\FilePathUtility;
use WebChemistry\Generette\Utility\FilesWriter;
use WebChemistry\Generette\Utility\PhpClassNaming;
use WebChemistry\Generette\Utility\PropertyGenerator;
use WebChemistry\Generette\Utility\UseStatements;
use WebChemistry\ServiceAttribute\Attribute\Service;

final class MessengerCommand extends GenerateCommand
{
	public static $defaultName = 'make:messenger';

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
		$this->setDescription('Creates new model')
			->addArgument('name', InputArgument::REQUIRED, 'The name of model')
			->addPropertiesOption()
			->addPropertiesOption('inject', 'Generate handler inject constructor');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		[$baseDir, $argumentName] = $this->extractBaseDirAndName($input->getArgument('name'));
		$properties = $this->getPropertiesOption($input, $output);
		$inject = $this->getPropertiesOption($input, $output, 'inject');

		$className = PhpClassNaming::createWithMerge($this->namespace, $argumentName);
		$handlerClassName = $className->withAppendedClassName('Handler');

		// message file
		$file = $this->createPhpFile();
		$namespace = $file->addNamespace($className->getNamespace());
		$this->useStatements = new UseStatements($namespace);
		$this->processMessageClass($class = $namespace->addClass($className->getClassName()));
		$constructor = $class->addMethod('__construct');

		PropertyGenerator::create($properties, $this->useStatements)
			->generateConstructor($constructor, true)
			->generateProperties($class, true)
			->generateGettersAndSetters($class, true);

		// handler file
		$handlerFile = $this->createPhpFile();
		$namespace = $handlerFile->addNamespace($handlerClassName->getNamespace());
		$this->useStatements = new UseStatements($namespace);
		$this->processHandlerClass($class = $namespace->addClass($handlerClassName->getClassName()), $className->getFullName());

		PropertyGenerator::create($inject, $this->useStatements)
			->generateConstructor($class->getMethod('__construct'), true);

		// directories
		$baseDir = FilePathUtility::join($this->basePath, $baseDir);

		FilesWriter::create($input, $output, $this->getHelper('question'))
			->addFile(
				FilePathUtility::join($baseDir, $className->getFileName()),
				$this->printer->printFile($file),
			)
			->addFile(
				FilePathUtility::join($baseDir, $handlerClassName->getFileName()),
				$this->printer->printFile($handlerFile),
			)
			->write();

		return self::SUCCESS;
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

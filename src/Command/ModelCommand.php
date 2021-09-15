<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\Utils\Strings;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WebChemistry\Generette\UI\Control;
use WebChemistry\Generette\UI\DefaultTemplate;
use WebChemistry\Generette\Utility\FilePathUtility;
use WebChemistry\Generette\Utility\FilesWriter;
use WebChemistry\Generette\Utility\PhpClassNaming;
use WebChemistry\Generette\Utility\UseStatements;
use WebChemistry\ServiceAttribute\Attribute\Service;

final class ModelCommand extends GenerateCommand
{

	public static $defaultName = 'make:model';

	public function __construct(
		private string $basePath,
		private string $namespace,
		private string $modelClass,
	)
	{
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->setDescription('Creates new model')
			->addArgument('name', InputArgument::REQUIRED, 'The name of model')
			->addOption('constructor', 'c');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		[$baseDir, $argumentName] = $this->extractBaseDirAndName($input->getArgument('name'));
		$constructor = $input->getOption('constructor');

		if (!str_ends_with($argumentName, 'Model')) {
			$argumentName .= 'Model';
		}

		$className = PhpClassNaming::createWithMerge($this->namespace, $argumentName);

		// component file
		$file = $this->createPhpFile();
		$namespace = $file->addNamespace($className->getNamespace());
		$this->useStatements = new UseStatements($namespace);
		$this->processModelClass($class = $namespace->addClass($className->getClassName()));
		if ($constructor) {
			$class->addMethod('__construct');
		}

		// directories
		$baseDir = FilePathUtility::join($this->basePath, $baseDir);

		FilesWriter::create($input, $output, $this->getHelper('question'))
			->addFile(
				FilePathUtility::join($baseDir, $className->getFileName()),
				$this->printer->printFile($file),
			)
			->write();

		return self::SUCCESS;
	}

	private function processModelClass(ClassType $class): void
	{
		$class->addExtend($this->useStatements->use($this->modelClass));
		$class->setFinal();

		if (class_exists(Service::class)) {
			$class->addAttribute($this->useStatements->use(Service::class));
		}
	}

}

<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command;

use Nette\PhpGenerator\ClassType;
use WebChemistry\Generette\Command\Argument\ModelArguments;
use WebChemistry\Generette\Utility\FilePath;
use WebChemistry\Generette\Utility\UseStatements;
use WebChemistry\ServiceAttribute\Attribute\Service;

final class ModelCommand extends GenerateCommand
{

	public static $defaultName = 'make:model';

	protected ModelArguments $arguments;

	public function __construct(
		private string $namespace,
		private string $modelClass,
	)
	{
		parent::__construct();
	}

	protected function exec(): void
	{
		$className = $this->createClassNameFromArguments($this->arguments, $this->namespace)
			->withAppendedClassName('Model', true);

		// component file
		$class = $this->createClassFromClassName($file = $this->createPhpFile(), $className);
		$this->processModelClass($class);

		// directories
		$this->createFilesWriter()
			->addFile(
				$baseDir->withAppendedPath($className->getFileName())->toString(),
				$this->printer->printFile($file),
			)
			->write();
	}

	private function processModelClass(ClassType $class): void
	{
		$class->addExtend($this->useStatements->use($this->modelClass));
		$class->setFinal();

		if (class_exists(Service::class)) {
			$class->addAttribute($this->useStatements->use(Service::class));
		}

		$class->addMethod('__construct');
	}

}

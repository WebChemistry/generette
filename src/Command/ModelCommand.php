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
		private string $basePath,
		private string $namespace,
		private string $modelClass,
	)
	{
		parent::__construct();
	}

	protected function exec(): void
	{
		$baseClassName = $this->createClassName($this->arguments->name)
			->withAppendedClassName('Model', true);
		$className = $baseClassName->withPrependedNamespace($this->namespace);

		// component file
		$file = $this->createPhpFile();
		$namespace = $file->addNamespace($className->getNamespace());
		$this->useStatements = new UseStatements($namespace);
		$this->processModelClass($class = $namespace->addClass($className->getClassName()));

		if ($this->arguments->constructor) {
			$class->addMethod('__construct');
		}

		// directories
		$baseDir = new FilePath($this->basePath, $baseClassName->getPath());

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
	}

}

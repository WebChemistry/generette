<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command;

use Nette\Application\UI\Presenter;
use Nette\PhpGenerator\ClassType;
use WebChemistry\Generette\Command\Argument\AdminPresenterArguments;
use WebChemistry\Generette\Utility\FilePath;

final class AdminPresenterCommand extends GenerateCommand
{

	protected static $defaultName = 'make:presenter:admin';

	protected AdminPresenterArguments $arguments;

	public function __construct(
		private string $basePath,
		private string $namespace,
		private string $baseClass = Presenter::class,
	)
	{
		parent::__construct();
	}

	protected function exec(): void
	{
		$baseClassName = $this->createClassName($this->arguments->name);

		$className = $baseClassName->withPrependedNamespace($this->namespace)->withAppendedClassName('Presenter', true);

		$file = $this->createPhpFile();
		$class = $this->createNamespaceFromFile($file, $className->getNamespace())->addClass($className->getClassName());
		$this->processClass($class);

		// directories
		$baseDir = new FilePath($this->basePath, $baseClassName->getPath());

		$this->createFilesWriter()
			->addFile(
				$baseDir->withAppendedPath($className->getFileName())->toString(),
				$this->printer->printFile($file)
			)
			->write();
	}

	private function processClass(ClassType $class): void
	{
		$class->setFinal();
		$class->addMethod('__construct');
		$class->addExtend($this->useStatements->use($this->baseClass));

		$method = $class->addMethod('utilize');
		$method->setReturnType('void');
		$method->addParameter('utils')
			->setType($this->useStatements->use('WebChemistry\AdminLTE\Utility\AdministrationUtility'));

		$method->addBody('$utils->createAction(\'default\', \'\')')
			->addBody("\t->run();");
	}

}

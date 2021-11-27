<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command;

use Nette\Application\UI\Presenter;
use Nette\PhpGenerator\ClassType;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WebChemistry\Generette\Command\Argument\PresenterArguments;
use WebChemistry\Generette\Utility\FilePathUtility;
use WebChemistry\Generette\Utility\FilesWriter;
use WebChemistry\Generette\Utility\PhpClassNaming;
use WebChemistry\Generette\Utility\UseStatements;
use WebChemistry\ServiceAttribute\Attribute\Service;

final class PresenterCommand extends GenerateCommand
{

	public static $defaultName = 'make:presenter';

	protected PresenterArguments $arguments;

	public function __construct(
		private string $namespace,
		private string $baseClass = Presenter::class,
	)
	{
		parent::__construct();
	}

	protected function exec(): void
	{
		$className = $this->createClassNameFromArguments($this->arguments, $this->namespace)
			->withAppendedClassName('Presenter', true);

		// file
		$class = $this->createClassFromClassName($file = $this->createPhpFile(), $className);
		$this->processClass($class);

		// writing
		$this->createFilesWriter()
			->addFile(
				$this->getFilePathFromClassName($className, $this->basePath, $this->namespace),
				$this->printer->printFile($file)
			)
			->write();
	}

	private function processClass(ClassType $class): void
	{
		$class->addMethod('__construct')
			->addBody('parent::__construct();');

		$class->setFinal();
		$class->addExtend($this->useStatements->use($this->baseClass));
	}

}

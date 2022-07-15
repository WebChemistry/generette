<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command;

use Nette\Application\UI\Presenter;
use Nette\PhpGenerator\ClassType;
use WebChemistry\Generette\Command\Argument\AdminPresenterArguments;

final class AdminPresenterCommand extends GeneretteCommand
{

	protected static $defaultName = 'make:presenter:admin';

	protected AdminPresenterArguments $arguments;

	public function __construct(
		private string $namespace,
		private string $baseClass = Presenter::class,
	)
	{
		parent::__construct();
	}

	protected function exec(): void
	{
		$className = $this->generette->createClassName($this->arguments->name, $this->namespace)
			->withAppendedClassName('Presenter', true);

		$this->processClass($this->generette->createClassType($className));

		$this->generette->finish();
	}

	private function processClass(ClassType $class): void
	{
		$class->setFinal();
		$class->addMethod('__construct')
			->addBody('parent::__construct();');
		$class->setExtends($this->generette->use($this->baseClass));

		$method = $class->addMethod('utilize');
		$method->setReturnType('void');
		$method->addParameter('utils')
			->setType($this->generette->use('WebChemistry\AdminLTE\Utility\AdministrationUtility'));

		$method->addBody('$utils->createAction(\'default\', \'\')')
			->addBody("\t->run();");
	}

}

<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command;

use Nette\Application\UI\Presenter;
use Nette\PhpGenerator\ClassType;
use WebChemistry\Generette\Command\Argument\PresenterArguments;

final class PresenterCommand extends GeneretteCommand
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
		$className = $this->generette->createClassName($this->arguments->name, $this->namespace)
			->withAppendedClassName('Presenter', true);

		$this->processClass(
			$this->generette->createClassType($className)
		);

		$this->generette->finish();
	}

	private function processClass(ClassType $class): void
	{
		$class->addMethod('__construct')
			->addBody('parent::__construct();');

		$class->setFinal();
		$class->setExtends($this->generette->use($this->baseClass));
	}

}

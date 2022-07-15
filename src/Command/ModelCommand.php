<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command;

use Nette\PhpGenerator\ClassType;
use WebChemistry\Generette\Command\Argument\ModelArguments;
use WebChemistry\ServiceAttribute\Attribute\Service;

final class ModelCommand extends GeneretteCommand
{

	public static $defaultName = 'make:model';

	protected ModelArguments $arguments;

	public function __construct(
		private string $namespace,
		private ?string $modelParentClass = null,
	)
	{
		parent::__construct();
	}

	protected function exec(): void
	{
		$className = $this->generette->createClassName($this->arguments->name, $this->namespace)
			->withAppendedClassName('Model', true);

		$this->processModelClass($this->generette->createClassType($className));

		$this->generette->finish();
	}

	private function processModelClass(ClassType $class): void
	{
		if ($parent = $this->modelParentClass) {
			$class->addExtend($this->generette->use($parent));
		}

		$class->setFinal();

		if (class_exists(Service::class)) {
			$class->addAttribute($this->generette->use(Service::class));
		}

		$class->addMethod('__construct');
	}

}

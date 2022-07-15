<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command;

use Nette\PhpGenerator\ClassType;
use WebChemistry\Console\Attribute\Argument;
use WebChemistry\Console\Attribute\Description;
use WebChemistry\Console\BaseCommand;
use WebChemistry\Generette\Command\Argument\CommandArguments;

final class CommandCommand extends GeneretteCommand
{

	protected static $defaultName = 'make:command';

	protected CommandArguments $arguments;

	public function __construct(
		private ?string $namespace = null,
		private string $baseCommandClass = BaseCommand::class,
	)
	{
		parent::__construct();
	}

	protected function exec(): void
	{
		$className = $this->generette->createClassName($this->arguments->name, $this->namespace)
			->withPrependedClassName('Command');

		$argumentsClassName = $this->generette->createClassName($this->arguments->name, $this->namespace)
			->withAppendedNamespace('Argument')
			->withPrependedClassName('Arguments');

		$this->processCommand($this->generette->createClassType($className), $argumentsClassName->getFullName());

		// arguments
		$this->processArguments($this->generette->createClassType($argumentsClassName));

		$this->generette->finish($this->arguments->overwrite);
	}

	private function processCommand(ClassType $class, string $argumentsClassName): void
	{
		$class->setFinal();
		$class->setExtends($this->generette->use($this->baseCommandClass));

		$class->addProperty('arguments')
			->setVisibility('protected')
			->setType($this->generette->use($argumentsClassName));

		$class->addMethod('exec')
			->setReturnType('void');
	}

	private function processArguments(ClassType $class): void
	{
		$class->setFinal();
		$class->addAttribute($this->generette->use(Description::class));

		$props = $this->arguments->props;
		if ($props) {
			$props->generate($this->generette, $class);

			$array = $props->toIndexedArray();

			foreach ($class->getProperties() as $property) {
				$property->addAttribute(Description::class);

				$extract = $array[$property->getName()] ?? null;
				if ($extract?->getFlag('arg')) {
					$property->addAttribute($this->generette->use(Argument::class));
				}
			}
		}
	}

}

<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command\Argument;

use WebChemistry\ConsoleArguments\Attribute\Argument;
use WebChemistry\ConsoleArguments\Attribute\Description;
use WebChemistry\ConsoleArguments\Attribute\Shortcut;

#[Description('Creates new component.')]
final class ComponentArguments implements ArgumentWithClassNameInterface
{

	#[Argument]
	#[Description('The name of component.')]
	public string $name;

	#[Shortcut('c')]
	public bool $constructor = false;

	public function getClassName(): string
	{
		return $this->name;
	}

}

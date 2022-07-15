<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command\Argument;

use WebChemistry\Console\Attribute\Argument;
use WebChemistry\Console\Attribute\Description;
use WebChemistry\Console\Attribute\Shortcut;

#[Description('Creates new component.')]
final class ComponentArguments
{

	#[Argument]
	#[Description('The name of component.')]
	public string $name;

	#[Shortcut('c')]
	public bool $constructor = false;

}

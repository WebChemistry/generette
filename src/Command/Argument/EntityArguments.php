<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command\Argument;

use WebChemistry\ConsoleArguments\Attribute\Argument;
use WebChemistry\ConsoleArguments\Attribute\Description;
use WebChemistry\ConsoleArguments\Attribute\Shortcut;

#[Description('Makes new entity.')]
final class EntityArguments
{

	#[Argument]
	#[Description('The name of entity.')]
	public string $name;

	#[Shortcut('i')]
	#[Description('Generate identifier.')]
	public bool $identifier = false;

}

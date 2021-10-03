<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command\Argument;

use WebChemistry\ConsoleArguments\Attribute\Argument;
use WebChemistry\ConsoleArguments\Attribute\Description;
use WebChemistry\ConsoleArguments\Attribute\Shortcut;

#[Description('Makes model')]
final class ModelArguments
{

	#[Description('The name of model')]
	#[Argument]
	public string $name;

	#[Description('Creates constructor')]
	#[Shortcut('c')]
	public bool $constructor;

}

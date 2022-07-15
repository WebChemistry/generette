<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command\Argument;

use WebChemistry\Console\Attribute\Argument;
use WebChemistry\Console\Attribute\Description;
use WebChemistry\Console\Attribute\Shortcut;

#[Description('Makes model')]
final class ModelArguments
{

	#[Description('The name of model')]
	#[Argument]
	public string $name;

}

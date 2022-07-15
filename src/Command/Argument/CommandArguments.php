<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command\Argument;

use WebChemistry\Console\Attribute\Argument;
use WebChemistry\Console\Attribute\Description;
use WebChemistry\Console\Attribute\Shortcut;
use WebChemistry\Generette\Property\Attribute\ConfigureProperties;
use WebChemistry\Generette\Property\Properties;

#[Description('Creates new command')]
final class CommandArguments extends GeneretteArguments
{

	#[Description('Name of command')]
	#[Argument]
	public string $name;

	#[Shortcut('p')]
	#[ConfigureProperties(flags: ['arg' => ['desc' => 'property as argument']], visibility: 'public')]
	public ?Properties $props;

}

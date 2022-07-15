<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command\Argument;

use WebChemistry\Console\Attribute\Argument;
use WebChemistry\Console\Attribute\Description;
use WebChemistry\Console\Attribute\Shortcut;
use WebChemistry\Generette\Property\Attribute\ConfigureProperties;
use WebChemistry\Generette\Property\Properties;

#[Description('Makes new entity.')]
final class EntityArguments
{

	#[Argument]
	#[Description('The name of entity.')]
	public string $name;

	#[ConfigureProperties(csFlag: true, getFlag: true, setFlag: true, flags: ['id' => ['desc' => 'generate identifier']])]
	#[Shortcut('p')]
	public ?Properties $props;

	#[Shortcut('i')]
	#[Description('Generate identifier.')]
	public bool $identifier = false;

}

<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command\Argument;

use WebChemistry\Console\Attribute\Argument;
use WebChemistry\Console\Attribute\Description;
use WebChemistry\Console\Attribute\Shortcut;
use WebChemistry\Generette\Property\Attribute\ConfigureProperties;
use WebChemistry\Generette\Property\Properties;

#[Description('Creates new message with handler')]
final class MessengerArguments
{

	#[Description('The name of message class')]
	#[Argument]
	public string $name;

	#[ConfigureProperties(flags: Properties::FLAG_CS_TRUE + Properties::FLAG_GET_TRUE + Properties::FLAG_PROM_TRUE)]
	#[Shortcut('p')]
	public ?Properties $props;

}

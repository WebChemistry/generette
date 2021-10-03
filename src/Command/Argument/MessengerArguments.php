<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command\Argument;

use WebChemistry\ConsoleArguments\Attribute\Argument;
use WebChemistry\ConsoleArguments\Attribute\Description;

#[Description('Creates new message with handler.')]
final class MessengerArguments
{

	#[Description('The name of message class.')]
	#[Argument]
	public string $name;



}
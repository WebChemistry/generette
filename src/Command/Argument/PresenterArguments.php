<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command\Argument;

use WebChemistry\Console\Attribute\Argument;
use WebChemistry\Console\Attribute\Description;

#[Description('Makes presenter')]
final class PresenterArguments
{

	#[Argument]
	#[Description('The name of presenter')]
	public string $name;

}

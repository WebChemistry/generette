<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command\Argument;

use WebChemistry\Console\Attribute\Description;
use WebChemistry\Console\Attribute\Shortcut;

abstract class GeneretteArguments
{

	#[Description('Overwrites existing files')]
	#[Shortcut('o')]
	public bool $overwrite = false;

}

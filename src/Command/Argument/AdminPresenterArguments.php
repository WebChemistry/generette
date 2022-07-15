<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command\Argument;

use WebChemistry\Console\Attribute\Argument;
use WebChemistry\Console\Attribute\Description;

#[Description('Makes admin presenter')]
final class AdminPresenterArguments
{

	#[Description('The name of presenter')]
	#[Argument]
	public string $name;

}

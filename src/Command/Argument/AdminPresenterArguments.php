<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command\Argument;

use WebChemistry\ConsoleArguments\Attribute\Argument;
use WebChemistry\ConsoleArguments\Attribute\Description;

#[Description('Makes admin presenter.')]
final class AdminPresenterArguments
{

	#[Argument('The name of presenter.')]
	public string $name;

}

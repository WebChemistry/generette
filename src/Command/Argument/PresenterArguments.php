<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command\Argument;

use WebChemistry\ConsoleArguments\Attribute\Argument;
use WebChemistry\ConsoleArguments\Attribute\Description;

#[Description('Makes presenter.')]
final class PresenterArguments implements ArgumentWithClassNameInterface
{

	#[Argument('The name of presenter.')]
	public string $name;

	public function getClassName(): string
	{
		return $this->name;
	}

}

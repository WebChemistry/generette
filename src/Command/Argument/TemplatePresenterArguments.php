<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command\Argument;

use WebChemistry\ConsoleArguments\Attribute\Argument;
use WebChemistry\ConsoleArguments\Attribute\Description;

final class TemplatePresenterArguments implements ArgumentWithClassNameInterface
{

	#[Argument('Name of presenter')]
	public string $presenter;

	#[Argument('Name of action')]
	public string $action;

	#[Description('Namespace of template')]
	public string $namespace;

	public function getClassName(): string
	{
		return $this->presenter;
	}

}

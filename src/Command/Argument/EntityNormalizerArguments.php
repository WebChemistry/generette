<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command\Argument;

use WebChemistry\ConsoleArguments\Attribute\Argument;
use WebChemistry\ConsoleArguments\Attribute\Description;
use WebChemistry\ConsoleArguments\Attribute\Shortcut;
use WebChemistry\ConsoleArguments\Extension\ValidateObjectInterface;

#[Description('Generates entity normalizer / denormalizer.')]
final class EntityNormalizerArguments implements ValidateObjectInterface
{

	#[Argument]
	#[Description('Normalizer name.')]
	public string $name;

	#[Description('Only normalizer.')]
	#[Shortcut('o')]
	public bool $normalizer = false;

	#[Description('Only denormalizer.')]
	#[Shortcut('d')]
	public bool $denormalizer = false;

	#[Description('Populate default object, pass object name.')]
	#[Shortcut('p')]
	public ?string $populate = null;

	#[Description('Check if data is array.')]
	#[Shortcut('a')]
	public bool $array = false;

	#[Description('Check if data is array.')]
	#[Shortcut('c')]
	public bool $constructor = false;

	public function validate(): void
	{
		if (!$this->denormalizer && !$this->normalizer) {
			$this->denormalizer = $this->normalizer = true;
		}
	}

}

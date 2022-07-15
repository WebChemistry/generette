<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Property\Attribute;

use Attribute;
use WebChemistry\Console\Attribute\Configuration;
use WebChemistry\Console\Exceptions\MismatchTypeException;
use WebChemistry\Generette\Property\Properties;

#[Attribute]
final class ConfigureProperties extends Configuration
{

	private Properties $properties;

	/**
	 * @param array<string, array{ desc: string, default?: bool }> $flags
	 */
	public function __construct(
		private array $flags = [],
		private string $description = 'Generate properties',
		private string $visibility = 'private',
	)
	{
		parent::__construct('processValue', 'getDescription');
	}

	private function getProperties(): Properties
	{
		return $this->properties ??= $this->createProperties();
	}

	private function createProperties(): Properties
	{
		return new Properties(
			$this->flags,
			$this->description,
			$this->visibility,
		);
	}

	public function getDescription(): string
	{
		return $this->getProperties()->getDescription();
	}

	public function processValue(mixed $value): ?Properties
	{
		if (!is_string($value) && $value !== null) {
			throw new MismatchTypeException('string|null', get_debug_type($value));
		}

		if (!$value) {
			return null;
		}

		return $this->getProperties()->parse($value);
	}

}

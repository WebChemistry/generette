<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Utility\ValueObject;

final class PropertyExtractedObject
{

	private const BOOL_MAP = [
		'yes' => true,
		'true' => true,
		'no' => false,
		'false' => false,
	];

	public function __construct(
		private string $name,
		private ?string $type,
		private array $flags,
		private ?string $default,
	)
	{
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getType(): ?string
	{
		return $this->type;
	}

	public function getFlag(string $flag, mixed $default = false): mixed
	{
		return $this->flags[$flag] ?? $default;
	}

	public function getDefault(): mixed
	{
		$type = $this->type;
		if ($type && $type[0] === '?') {
			if (!$this->default || $this->default === 'null') {
				return null;
			}

			$type = substr($type, 1);
		}

		return match ($this->type) {
			'int' => (int) $this->default,
			'bool' => self::BOOL_MAP[strtolower($this->default)],
			'float' => (float) $this->default,
			default => $this->default,
		};
	}

	public function hasDefault(): bool
	{
		return $this->default !== null;
	}

}

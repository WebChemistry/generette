<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Utility\Result;

use Nette\Utils\Type;
use WebChemistry\Generette\Utility\Generette;
use WebChemistry\Generette\Utility\PhpClassName;

final class PropertyExtractedResult
{

	private const BOOL_MAP = [
		'yes' => true,
		'true' => true,
		'no' => false,
		'false' => false,
	];

	/**
	 * @param array<string, bool> $flagsDefaults
	 * @param array<string, bool> $flags
	 */
	public function __construct(
		private string $name,
		private ?string $type,
		private array $flags,
		private array $flagsDefaults,
		private ?string $default,
		private ?string $visibility,
	)
	{
		if ($this->type) {
			$this->type = strtr($this->type, ['/' => '\\']);
		}
	}

	public function getVisibility(): ?string
	{
		return $this->visibility;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getType(): ?string
	{
		return $this->type;
	}

	public function useType(Generette $generette, bool $fullName = true): ?string
	{
		if (!$this->type) {
			return null;
		}

		$type = Type::fromString($this->type);

		$names = [];
		foreach ($type->getTypes() as $singleType) {
			$name = $singleType->getSingleName();

			if (!$name) {
				continue;
			}

			if ($singleType->isClass()) {
				$name = (new PhpClassName($name))
					->withMap(fn (string $name): string => ucfirst($name))
					->getFullName();

				$names[] = $generette->use($name, $fullName);
			} else {
				$names[] = $name;
			}
		}

		return (string) Type::fromString(implode($type->isIntersection() ? '&' : '|', $names));
	}

	public function hasFlag(string $flag): bool
	{
		return array_key_exists($flag, $this->flags);
	}

	public function getFlag(string $flag): bool
	{
		return $this->flags[$flag] ?? $this->flagsDefaults[$flag] ?? false;
	}

	public function setFlag(string $flag, bool $value): self
	{
		$this->flags[$flag] = $value;

		return $this;
	}

	public function setFlagIfNotSet(string $flag, bool $value): self
	{
		if (!$this->hasFlag($flag)) {
			$this->flags[$flag] = $value;
		}

		return $this;
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

		return match ($type) {
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

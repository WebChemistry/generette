<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Property;

use LogicException;
use Nette\Utils\Json;
use Nette\Utils\Type;

final class Property
{

	public const PUBLIC = 'public';
	public const PROTECTED = 'protected';
	public const PRIVATE = 'private';

	/** @var array<string, bool> */
	private $flagIndex = [];

	/**
	 * @param string[] $flags
	 */
	public function __construct(
		private ?string $visibility,
		private string $name,
		private ?Type $type,
		private ?string $default,
		private bool $defaultSurrounded,
		private array $flags,
	)
	{
		foreach ($this->flags as $flag) {
			$this->flagIndex[ltrim($flag, '!')] = true;
		}

		if (!$this->type && $this->hasDefault()) {
			$this->type = Type::fromString(get_debug_type($this->convertDefault()));
		}
	}

	public function getVisibilityWithDefault(string $default): string
	{
		return $this->visibility ?? $default;
	}

	public function getVisibilityRequired(): string
	{
		if (!$this->visibility) {
			throw new LogicException('Visibility has not been set.');
		}

		return $this->visibility;
	}

	public function getVisibility(): ?string
	{
		return $this->visibility;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getType(): ?Type
	{
		return $this->type;
	}

	public function getTypeRequired(): Type
	{
		if (!$this->type) {
			throw new LogicException('Type has not been set.');
		}

		return $this->type;
	}

	/**
	 * @return string[]
	 */
	public function getFlags(): array
	{
		return $this->flags;
	}

	public function hasFlag(string $flag): bool
	{
		return isset($this->flagIndex[$flag]);
	}

	public function getFlagValue(string $flag): bool
	{
		if (!$this->hasFlag($flag)) {
			throw new LogicException(sprintf('Flag %s not set.', $flag));
		}

		return in_array($flag, $this->flags, true);
	}

	public function getDefault(): ?string
	{
		return $this->default;
	}

	public function hasDefault(): bool
	{
		return $this->default !== null;
	}

	public function isDefaultSurrounded(): bool
	{
		return $this->defaultSurrounded;
	}

	public function convertDefault(): mixed
	{
		if ($this->default === null) {
			return null;
		}

		if ($this->defaultSurrounded) {
			if (str_starts_with($this->default, '[')) {
				return Json::decode($this->default, Json::FORCE_ARRAY);
			}

			return $this->default;
		}

		if (strcasecmp('null', $this->default) === 0) {
			return null;
		}

		if (strcasecmp('true', $this->default) === 0) {
			return true;
		}

		if (strcasecmp('false', $this->default) === 0) {
			return false;
		}

		if (!is_numeric($this->default)) {
			return $this->default;
		}

		if (str_contains($this->default, '.')) {
			return (float) $this->default;
		}

		return (int) $this->default;
	}

	public function getFlagValueWithDefault(string $flag, bool $default): bool
	{
		if (!$this->hasFlag($flag)) {
			return $default;
		}

		return in_array($flag, $this->flags, true);
	}

}

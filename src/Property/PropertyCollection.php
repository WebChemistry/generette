<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Property;

use ArrayIterator;
use IteratorAggregate;
use Nette\Utils\Type;

/**
 * @implements IteratorAggregate<int, Property>
 */
final class PropertyCollection implements IteratorAggregate
{

	/**
	 * @param array<string, Property> $collection
	 */
	public function __construct(
		private array $collection = [],
	)
	{
	}

	/**
	 * @return Property[]
	 */
	public function getCollection(): array
	{
		return $this->collection;
	}

	/**
	 * @return ArrayIterator<int, Property>
	 */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->collection);
	}

	/**
	 * @param array<string, bool> $flags
	 */
	public function withDefaults(?string $visibility = null, ?Type $type = null, array $flags = []): self
	{
		$collection = [];

		foreach ($this->collection as $property) {
			$newFlags = $property->getFlags();

			foreach ($flags as $name => $value) {
				if (!$property->hasFlag($name)) {
					$newFlags[] = ($value ? '' : '!') . $name;
				}
			}

			$collection[] = new Property(
				$visibility ? $property->getVisibilityWithDefault($visibility) : $property->getVisibility(),
				$property->getName(),
				$property->getType() ?? $type,
				$property->getDefault(),
				$property->isDefaultSurrounded(),
				$newFlags,
			);
		}

		return new self($collection);
	}

}

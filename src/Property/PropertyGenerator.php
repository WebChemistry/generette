<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Property;

use BadMethodCallException;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use WebChemistry\Generette\Utility\Generette;
use WebChemistry\Generette\Utility\Result\PropertyExtractedResult;

final class PropertyGenerator
{

	/**
	 * @param PropertyExtractedResult[] $properties
	 */
	public function __construct(
		private array $properties,
		private Generette $generette,
	)
	{
	}

	/**
	 * @param PropertyExtractedResult[] $properties
	 */
	public static function create(array $properties, Generette $generette): self
	{
		return new self($properties, $generette);
	}

	public function generateProperties(ClassType $class): self
	{
		foreach ($this->properties as $property) {
			$isInConstructor = $property->getFlag('cs');

			if ($isInConstructor && $property->getFlag('prom')) {
				continue;
			}

			$prop = $class->addProperty($property->getName());
			$prop->setVisibility($property->getVisibility() ?? 'private');

			$prop->setType($property->useType($this->generette));

			if ($property->hasDefault() && !$isInConstructor) {
				$prop->setValue($property->getDefault());
			}
		}

		return $this;
	}

	public function generateConstructor(?Method $method = null, ?ClassType $classType = null): self
	{
		if (!$method && !$classType) {
			throw new BadMethodCallException('Method or classType must be passed.');
		}

		foreach ($this->properties as $property) {
			if (!$property->getFlag('cs')) {
				continue;
			}

			if (!$method && $classType) {
				if (!$classType->hasMethod('__construct')) {
					$classType->addMethod('__construct');
				}

				$method = $classType->getMethod('__construct');
			}

			if ($property->getFlag('prom')) {
				$parameter = $method->addPromotedParameter($property->getName());
				$parameter->setVisibility($property->getVisibility() ?? 'private');
			} else {
				$parameter = $method->addParameter($property->getName());
			}

			$parameter->setType($property->useType($this->generette));

			if ($property->hasDefault()) {
				$parameter->setDefaultValue($property->getDefault());
			}

			if (!$property->getFlag('prom')) {
				$method->addBody('$this->? = $?;', [$property->getName(), $property->getName()]);
			}
		}

		return $this;
	}

	public function generateGettersAndSetters(ClassType $class): self
	{
		foreach ($this->properties as $property) {
			if ($property->getFlag('get')) {
				$prefix = $property->getType() === 'bool' ? 'is' : 'get';

				$method = $class->addMethod($prefix . ucfirst($property->getName()));

				$method->setReturnType($property->useType($this->generette));
				$method->addBody('return $this->?;', [$property->getName()]);
			}

			if ($property->getFlag('set')) {
				$method = $class->addMethod('set' . ucfirst($property->getName()));

				$parameter = $method->addParameter($property->getName());
				if ($type = $property->getType()) {
					$parameter->setType($type);
				}

				$method->setReturnType('static');

				$method->addBody('$this->? = $?;', [$property->getName(), $property->getName()]);
				$method->addBody('');
				$method->addBody('return $this;');
			}
		}

		return $this;
	}

}

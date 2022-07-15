<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Utility;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
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

			if ($property->getFlag('pub')) {
				$prop->setPublic();
			} elseif ($property->getFlag('pr')) {
				$prop->setProtected();
			} else {
				$prop->setPrivate();
			}

			$prop->setType($property->useType($this->generette));

			if ($property->hasDefault() && !$isInConstructor) {
				$prop->setValue($property->getDefault());
			}
		}

		return $this;
	}

	public function generateConstructor(Method $method): self
	{
		foreach ($this->properties as $property) {
			if (!$property->getFlag('cs')) {
				continue;
			}

			if ($property->getFlag('prom')) {
				$parameter = $method->addPromotedParameter($property->getName());

				if ($property->getFlag('pub')) {
					$parameter->setPublic();
				} elseif ($property->getFlag('pr')) {
					$parameter->setProtected();
				} else {
					$parameter->setPrivate();
				}
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

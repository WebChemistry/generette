<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Utility;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use WebChemistry\Generette\Utility\ValueObject\PropertyExtractedObject;

final class PropertyGenerator
{

	/**
	 * @param PropertyExtractedObject[] $properties
	 */
	public function __construct(
		private array $properties,
		private UseStatements $useStatements,
		private bool $promotedProperties = true,
	)
	{
	}

	/**
	 * @param PropertyExtractedObject[] $properties
	 */
	public static function create(array $properties, UseStatements $useStatements, bool $promotedProperties = true): self
	{
		return new self($properties, $useStatements, $promotedProperties);
	}

	public function generateProperties(ClassType $class, bool $defaultConstructorFlag = false): self
	{
		foreach ($this->properties as $property) {
			$isInConstructor = $property->getFlag('cs', $defaultConstructorFlag);

			if ($isInConstructor && $this->promotedProperties) {
				continue;
			}

			$prop = $class->addProperty($property->getName());
			$prop->setPrivate();

			if ($type = $property->getType()) {
				$prop->setType($this->useStatements->use($type));
			}

			if ($property->hasDefault() && !$isInConstructor) {
				$prop->setValue($property->getDefault());
			}
		}

		return $this;
	}

	public function generateConstructor(Method $method, bool $default = false, bool $promoted = true): self
	{
		foreach ($this->properties as $property) {
			if (!$property->getFlag('cs', $default)) {
				continue;
			}

			if ($this->promotedProperties) {
				$parameter = $method->addPromotedParameter($property->getName());
				$parameter->setPrivate();
			} else {
				$parameter = $method->addParameter($property->getName());
			}

			if ($type = $property->getType()) {
				$parameter->setType($this->useStatements->use($type));
			}

			if ($property->hasDefault()) {
				$parameter->setDefaultValue($property->getDefault());
			}

			if (!$this->promotedProperties) {
				$method->addBody('$this->? = $?;', [$property->getName(), $property->getName()]);
			}
		}

		return $this;
	}

	public function generateGettersAndSetters(ClassType $class, bool $defaultGetters = false, bool $defaultSetters = false): self
	{
		foreach ($this->properties as $property) {
			if ($property->getFlag('get', $defaultGetters)) {
				$prefix = $property->getType() === 'bool' ? 'is' : 'get';

				$method = $class->addMethod($prefix . ucfirst($property->getName()));

				if ($type = $property->getType()) {
					$method->setReturnType($this->useStatements->use($type));
				}

				$method->addBody('return $this->?;', [$property->getName()]);
			}

			if ($property->getFlag('set', $defaultSetters)) {
				$method = $class->addMethod('set' . ucfirst($property->getName()));

				$parameter = $method->addParameter($property->getName());
				if ($type = $property->getType()) {
					$parameter->setType($type);
				}

				$method->setReturnType($class->isFinal() ? 'self' : 'static');

				$method->addBody('$this->? = $?;', [$property->getName(), $property->getName()]);
				$method->addBody('');
				$method->addBody('return $this;');
			}
		}

		return $this;
	}

}

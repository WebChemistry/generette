<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Property;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;

final class PropertyGenerator
{

	public function __construct(
		private PropertyCollection $collection,
	)
	{
	}

	public function generateProperties(ClassType $class): bool
	{
		$used = false;
		foreach ($this->collection->getCollection() as $property) {
			$cs = $property->getFlagValueWithDefault('cs', false);
			$pr = $property->getFlagValueWithDefault('pr', false);

			if ($pr) {
				continue;
			}

			$prop = $class->addProperty($property->getName());
			$prop->setVisibility($property->getVisibility());

			if ($type = $property->getType()) {
				$prop->setType((string) $type);
			}

			if ($property->hasDefault() && !$cs) {
				$prop->setValue($property->convertDefault());
			}

			$used = true;
		}

		return $used;
	}

	public function generateConstructor(Method $method): bool
	{
		$used = false;
		foreach ($this->collection->getCollection() as $property) {
			$cs = $property->getFlagValueWithDefault('cs', false);
			$pr = $property->getFlagValueWithDefault('pr', false);
			if (!$cs && !$pr) {
				continue;
			}

			if ($pr) {
				$parameter = $method->addPromotedParameter($property->getName());
				$parameter->setPrivate();
			} else {
				$parameter = $method->addParameter($property->getName());
			}

			if ($type = $property->getType()) {
				$parameter->setType((string) $type);
			}

			if ($property->hasDefault()) {
				$parameter->setDefaultValue($property->convertDefault());
			}

			if (!$pr) {
				$method->addBody('$this->? = $?;', [$property->getName(), $property->getName()]);
			}

			$used = true;
		}

		return $used;
	}

	public function generateGettersAndSetters(ClassType $class): bool
	{
		$used = false;

		foreach ($this->collection->getCollection() as $property) {
			$get = $property->getFlagValueWithDefault('get', false);
			$set = $property->getFlagValueWithDefault('set', false);
			$type = $property->getType();
			if ($get) {
				$prefix = (string) $type === 'bool' ? 'is' : 'get';

				$method = $class->addMethod($prefix . ucfirst($property->getName()));

				if ($type) {
					$method->setReturnType((string) $type);
				}

				$method->addBody('return $this->?;', [$property->getName()]);
			}

			if ($set) {
				$method = $class->addMethod('set' . ucfirst($property->getName()));

				$parameter = $method->addParameter($property->getName());
				if ($type) {
					$parameter->setType((string) $type);
				}

				$method->setReturnType($class->isFinal() ? 'self' : 'static');

				$method->addBody('$this->? = $?;', [$property->getName(), $property->getName()]);
				$method->addBody('');
				$method->addBody('return $this;');
			}

			$used = true;
		}

		return $used;
	}

}

<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Nette\PhpGenerator\ClassType;
use Nette\Utils\Reflection;
use Symfony\Component\String\Inflector\EnglishInflector;
use WebChemistry\Generette\Command\Argument\EntityArguments;

final class EntityCommand extends GeneretteCommand
{

	public static $defaultName = 'make:entity';

	protected EntityArguments $arguments;

	public function __construct(
		private string $namespace,
	)
	{
		parent::__construct();
	}

	protected function exec(): void
	{
		$className = $this->generette->createClassName($this->arguments->name, $this->namespace);

		$class = $this->generette->createClassType($className);

		$this->generette->namespace?->addUse('Doctrine\\ORM\\Mapping', 'ORM');

		$this->processEntityClass($class);

		// properties
		if ($props = $this->arguments->props) {
			foreach ($props->toArray() as $property) {
				if ($property->hasFlag('id')) {
					$property->setFlagIfNotSet('set', false);
					$property->setFlagIfNotSet('cs', false);
				}
			}

			$props->generateAll($this->generette, $class);

			foreach ($props->toArray() as $property) {
				$prop = $class->getProperty($property->getName());

				$classType = $property->getType() && !Reflection::isBuiltinType($property->getType());
				if ($property->getFlag('id')) {
					$prop->addAttribute(Id::class);
					if (!$classType) {
						$prop->addAttribute(GeneratedValue::class);
					}
				}

				if ($classType) {
					$prop->addAttribute(ManyToOne::class);
					$prop->addAttribute(JoinColumn::class, [
						'nullable' => false,
						'onDelete' => 'CASCADE',
					]);
				} else {
					$prop->addAttribute(Column::class);
				}
			}
		}

		$this->generette->finish();
	}

	private function processEntityClass(ClassType $class): void
	{
		$tableName = preg_replace_callback(
			'#[A-Z]#',
			fn (array $matches) => '_' . lcfirst($matches[0]),
			lcfirst($class->getName()),
		);

		if (($pos = strrpos($tableName, '_')) !== false) {
			$pluralize = (new EnglishInflector())->pluralize(substr($tableName, $pos + 1))[0];
			$tableName = substr($tableName, 0, $pos + 1) . $pluralize;
		} else {
			$tableName = (new EnglishInflector())->pluralize($tableName)[0];
		}

		$class->addAttribute(Entity::class);
		$class->addAttribute(Table::class, ['name' => $tableName]);

		if ($this->arguments->identifier) {
			$property = $class->addProperty('id');
			$property->setPrivate();
			$property->setType('int');

			$property->addAttribute(Id::class);
			$property->addAttribute(Column::class);
			$property->addAttribute(GeneratedValue::class);

			$method = $class->addMethod('getId');
			$method->addBody('return $this->id;');
			$method->setPublic();
			$method->setReturnType('int');
		}
	}

}

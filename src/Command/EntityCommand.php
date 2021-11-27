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
use Symfony\Component\String\Inflector\EnglishInflector;
use WebChemistry\Generette\Command\Argument\EntityArguments;
use WebChemistry\Generette\Property\PropertiesOption;
use WebChemistry\Generette\Utility\FilePath;
use WebChemistry\Generette\Utility\PropertyGenerator;
use WebChemistry\Generette\Utility\UseStatements;

final class EntityCommand extends GenerateCommand
{

	public static $defaultName = 'make:entity';

	protected EntityArguments $arguments;

	private PropertiesOption $propertiesOption;

	public function __construct(
		private string $namespace,
	)
	{
		parent::__construct();
	}

	protected function configure(): void
	{
		parent::configure();

		$this->propertiesOption = $this->createPropertiesOption(shortcut: 'p')
			->setPromotedFlag(false)
			->setConstructorFlag(true)
			->setGetterFlag(true)
			->setSetterFlag(true)
			->addFlag('id', 'creates id')
			->initialize();
	}

	protected function exec(): void
	{
		$className = $this->createClassNameFromArguments($this->arguments, $this->namespace);

		// component file
		$this->createClassFromClassName($file = $this->createPhpFile(), $className);
		$this->useStatements->use('Doctrine\\ORM\\Mapping', alias: 'ORM');
		$this->processEntityClass($class = $namespace->addClass($className->getClassName()));
		$constructor = $class->addMethod('__construct');

		foreach ($this->propertiesOption->getAll() as $property) {
			if ($property->hasFlag('id')) {
				$property->setFlagIfNotSet('set', false);
				$property->setFlagIfNotSet('cs', false);
			}
		}

		$this->propertiesOption->setUseStatements($this->useStatements)
			->generateProperties($class)
			->generateGettersAndSetters($class)
			->generateConstructor($constructor);

		foreach ($this->propertiesOption->getAll() as $property) {
			$prop = $class->getProperty($property->getName());
			$classType = $property->getType() && !UseStatements::isBuiltIn($property->getType());
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

		// directories
		$this->createFilesWriter()
			->addFile(
				$this->getFilePathFromClassName($className),
				$this->printer->printFile($file),
			)
			->write();
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

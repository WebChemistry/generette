<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
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
		private string $basePath,
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
		$baseClassName = $this->createClassName($this->arguments->name);

		$className = $baseClassName->withPrependedNamespace($this->namespace);

		// component file
		$file = $this->createPhpFile();
		$namespace = $file->addNamespace($className->getNamespace());
		$namespace->addUse('Doctrine\\ORM\\Mapping', 'ORM');
		$this->useStatements = new UseStatements($namespace);
		$this->processEntityClass($class = $namespace->addClass($className->getClassName()));
		$constructor = $class->addMethod('__construct');

		$this->propertiesOption->setUseStatements($this->useStatements)
			->generateProperties($class)
			->generateGettersAndSetters($class)
			->generateConstructor($constructor);

		foreach ($this->propertiesOption->getAll() as $property) {
			$prop = $class->getProperty($property->getName());
			if ($property->getFlag('id')) {
				$prop->addAttribute(Id::class)->addAttribute(GeneratedValue::class);
			}

			$prop->addAttribute(Column::class);
		}

		// directories
		$baseDir = new FilePath($this->basePath, $baseClassName->getPath());

		$this->createFilesWriter()
			->addFile(
				$baseDir->withAppendedPath($className->getFileName())->toString(),
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

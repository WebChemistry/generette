<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Nette\PhpGenerator\ClassType;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\String\Inflector\EnglishInflector;
use WebChemistry\Generette\Utility\FilePathUtility;
use WebChemistry\Generette\Utility\FilesWriter;
use WebChemistry\Generette\Utility\PhpClassNaming;
use WebChemistry\Generette\Utility\PropertyGenerator;
use WebChemistry\Generette\Utility\UseStatements;

final class EntityCommand extends GenerateCommand
{

	public static $defaultName = 'make:entity';

	public function __construct(
		private string $basePath,
		private string $namespace,
	)
	{
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->setDescription('Creates new entity')
			->addArgument('name', InputArgument::REQUIRED, 'The name of entity')
			->addOption('identifier', 'i', InputOption::VALUE_NONE, 'Generate identifier')
			->addPropertiesOption();
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		[$baseDir, $argumentName] = $this->extractBaseDirAndName($input->getArgument('name'));
		$identifier = (bool) $input->getOption('identifier');
		$properties = $this->getPropertiesOption($input, $output);

		$className = PhpClassNaming::createWithMerge($this->namespace, $argumentName);

		// component file
		$file = $this->createPhpFile();
		$namespace = $file->addNamespace($className->getNamespace());
		$namespace->addUse('Doctrine\\ORM\\Mapping', 'ORM');
		$this->useStatements = new UseStatements($namespace);
		$this->processEntityClass($class = $namespace->addClass($className->getClassName()), $identifier);
		$constructor = $class->addMethod('__construct');

		// directories
		$baseDir = FilePathUtility::join($this->basePath, $baseDir);

		PropertyGenerator::create($properties, $this->useStatements, false)
			->generateProperties($class, true)
			->generateConstructor($constructor, true)
			->generateGettersAndSetters($class, true, true);

		foreach ($properties as $property) {
			$prop = $class->getProperty($property->getName());
			if ($property->getFlag('id')) {
				$prop->addAttribute(Id::class)->addAttribute(GeneratedValue::class);
			}

			$prop->addAttribute(Column::class);
		}

		FilesWriter::create($input, $output, $this->getHelper('question'))
			->addFile(
				FilePathUtility::join($baseDir, $className->getFileName()),
				$this->printer->printFile($file),
			)
			->write();

		return self::SUCCESS;
	}

	private function processEntityClass(ClassType $class, bool $identifier): void
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

		if ($identifier) {
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

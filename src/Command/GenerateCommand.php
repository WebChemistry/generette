<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command;

use LogicException;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WebChemistry\ConsoleArguments\BaseCommand;
use WebChemistry\Generette\Command\Argument\ArgumentWithClassNameInterface;
use WebChemistry\Generette\Composer\ComposerPathAutoload;
use WebChemistry\Generette\Printer\DefaultPrinter;
use WebChemistry\Generette\Property\PropertiesOption;
use WebChemistry\Generette\Utility\FilePath;
use WebChemistry\Generette\Utility\FilesWriter;
use WebChemistry\Generette\Utility\PhpClassNaming;
use WebChemistry\Generette\Utility\UseStatements;

abstract class GenerateCommand extends BaseCommand
{

	protected Printer $printer;

	protected UseStatements $useStatements;

	protected ComposerPathAutoload $composer;

	protected array $suggestionPaths = [];

	/** @var PropertiesOption[] */
	private array $properties = [];

	public function __construct()
	{
		$this->printer = new DefaultPrinter();

		parent::__construct();
	}

	public function setComposer(?string $composer): static
	{
		if ($composer) {
			$this->composer = new ComposerPathAutoload($composer);
		} else {
			unset($this->composer);
		}

		return $this;
	}

	protected function initialize(InputInterface $input, OutputInterface $output): void
	{
		foreach ($this->properties as $property) {
			$property->setSuggestionPaths($this->suggestionPaths);
		}
	}

	public function addSuggestionPath(string $suggestionPath): void
	{
		$this->suggestionPaths[] = $suggestionPath;
	}

	protected function createPropertiesOption(
		string $name = 'properties',
		?string $shortcut = null,
		string $description = 'Generate properties'
	): PropertiesOption
	{
		return $this->properties[] = new PropertiesOption($this, $name, $shortcut, $description);
	}

	protected function createPhpFile(): PhpFile
	{
		$file = new PhpFile();
		$file->setStrictTypes();

		return $file;
	}

	protected function createClassFromClassName(PhpFile $file, PhpClassNaming $className): ClassType
	{
		$namespace = $this->createNamespaceFromFile($file, $className->getNamespace());

		return $namespace->addClass($className->getClassName());
	}

	protected function createInterfaceFromClassName(PhpFile $file, PhpClassNaming $className): ClassType
	{
		$namespace = $this->createNamespaceFromFile($file, $className->getNamespace());

		return $namespace->addInterface($className->getClassName());
	}

	protected function createFilesWriter(): FilesWriter
	{
		return new FilesWriter($this->input, $this->output, $this->getHelper('question'));
	}

	protected function createClassNameFromArguments(ArgumentWithClassNameInterface $arguments, ?string $namespacePrefix): PhpClassNaming
	{
		$className = $this->createClassName($arguments->getClassName());
		if (
			$namespacePrefix &&
			!str_starts_with($arguments->getClassName(), '\\') &&
			!str_starts_with($arguments->getClassName(), '/')
		) {
			$className = $className->withPrependedNamespace($namespacePrefix);
		}

		return $className;
	}

	protected function createClassName(string $fullName): PhpClassNaming
	{
		$className = new PhpClassNaming($fullName);
		$className = $className->withMapped(
			fn (string $token) => ucfirst($token),
		);

		return $className;
	}

	protected function createNamespaceFromFile(PhpFile $file, string $namespace): PhpNamespace
	{
		$namespace = $file->addNamespace($namespace);
		$this->useStatements = new UseStatements($namespace);

		return $namespace;
	}

	protected function getFilePathFromClassName(
		PhpClassNaming $className,
		?string $baseDir = null,
		?string $baseNamespace = null
	): string
	{
		if (!$baseDir) {
			if (!$this->composer) {
				throw new LogicException('$baseDir or composer file path must be set.');
			}

			return $this->composer->resolvePathByPsr4Namespace($className->getNamespace()) . '/' . $className->getFileName();
		}

		if (!$baseNamespace) {
			throw new LogicException('Argument $baseNamespace must be set.');
		}

		$baseClassName = $className->withRemovedNamespaceFromStart($baseNamespace);

		return (new FilePath($baseDir, $baseClassName->getPath()))->withAppendedPath($baseClassName->getFileName())
			->toString();
	}

}

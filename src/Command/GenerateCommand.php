<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command;

use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use WebChemistry\ConsoleArguments\BaseCommand;
use WebChemistry\Generette\Printer\DefaultPrinter;
use WebChemistry\Generette\Property\PropertiesOption;
use WebChemistry\Generette\Utility\FilesWriter;
use WebChemistry\Generette\Utility\PhpClassNaming;
use WebChemistry\Generette\Utility\UseStatements;

abstract class GenerateCommand extends BaseCommand
{

	protected Printer $printer;

	protected UseStatements $useStatements;

	protected array $suggestionPaths = [];

	public function __construct()
	{
		$this->printer = new DefaultPrinter();

		parent::__construct();
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
		return (new PropertiesOption($this, $name, $shortcut, $description))
			->setSuggestionPaths($this->suggestionPaths);
	}

	protected function createPhpFile(): PhpFile
	{
		$file = new PhpFile();
		$file->setStrictTypes();

		return $file;
	}

	protected function createFilesWriter(): FilesWriter
	{
		return new FilesWriter($this->input, $this->output, $this->getHelper('question'));
	}

	protected function createClassName(string $fullName): PhpClassNaming
	{
		return new PhpClassNaming($fullName);
	}

	protected function createNamespaceFromFile(PhpFile $file, string $namespace): PhpNamespace
	{
		$namespace = $file->addNamespace($namespace);
		$this->useStatements = new UseStatements($namespace);

		return $namespace;
	}

}

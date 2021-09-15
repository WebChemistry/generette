<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Language;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WebChemistry\Generette\Printer\DefaultPrinter;
use WebChemistry\Generette\Utility\PropertyExtractor;
use WebChemistry\Generette\Utility\UseStatements;
use WebChemistry\Generette\Utility\ValueObject\PropertyExtractedObject;

abstract class GenerateCommand extends Command
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

	/**
	 * @return PropertyExtractedObject[]
	 */
	protected function getPropertiesOption(InputInterface $input, OutputInterface $output, string $name = 'properties'): array
	{
		return PropertyExtractor::extract(
			$input->getOption($name),
			$output,
			$input,
			$this->getHelper('question'),
			$this->suggestionPaths,
		);
	}

	protected function addPropertiesOption(string $name = 'properties', string $description = 'Generate properties'): static
	{
		$this->addOption(
			$name,
			null,
			InputOption::VALUE_REQUIRED,
			sprintf("%s. Examples: property:int,property2:entities/comment || property@flag || property=default\n", $description) .
			"Flags:\n@cs - generate property in constructor\n@get - generate getter\n@set - generate setter\n@!set - don't generate setter etc."
		);

		return $this;
	}

	protected function createPhpFile(): PhpFile
	{
		$file = new PhpFile();
		$file->setStrictTypes();

		return $file;
	}

	protected function error(string $message): string
	{
		return '<error>' . $message . '</error>';
	}

	#[ArrayShape(['string', 'string'])]
	protected function extractBaseDirAndName(string $name): array
	{
		return [str_contains($name, '/') ? dirname($name) : '', strtr($name, ['/' => '\\'])];
	}

	protected function createNamespaceFromFile(PhpFile $file, string $namespace): PhpNamespace
	{
		$namespace = $file->addNamespace($namespace);
		$this->useStatements = new UseStatements($namespace);

		return $namespace;
	}

}

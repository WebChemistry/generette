<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Language;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WebChemistry\Generette\Printer\DefaultPrinter;
use WebChemistry\Generette\Utility\UseStatements;

abstract class GenerateCommand extends Command
{

	protected Printer $printer;

	protected UseStatements $useStatements;

	public function __construct()
	{
		$this->printer = new DefaultPrinter();

		parent::__construct();
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

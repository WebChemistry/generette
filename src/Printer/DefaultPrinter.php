<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Printer;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Dumper;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\Helpers;
use Nette\PhpGenerator\InterfaceType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use Nette\PhpGenerator\PromotedParameter;
use Nette\PhpGenerator\TraitType;
use Nette\PhpGenerator\Type;
use Nette\Utils\Strings;

final class DefaultPrinter extends Printer
{

	public function __construct()
	{
		parent::__construct();

		$this->linesBetweenMethods = 1;
		$this->linesBetweenProperties = 1;
	}

	public function printFile(PhpFile $file): string
	{
		$namespaces = [];
		foreach ($file->getNamespaces() as $namespace) {
			$namespaces[] = $this->printNamespace($namespace);
		}

		return Strings::normalize(
				"<?php"
				. ($file->hasStrictTypes() ? " declare(strict_types = 1);\n" : '')
				. ($file->getComment() ? "\n" . Helpers::formatDocComment($file->getComment() . "\n") : '')
				. "\n"
				. implode("\n\n", $namespaces)
			) . "\n";
	}

	public function printClass(ClassType|InterfaceType|TraitType|EnumType $class, PhpNamespace $namespace = null): string
	{
		$lines = explode("\n", parent::printClass($class, $namespace));
		foreach ($lines as $i => $line) {
			if (preg_match('#^\s*(final|abstract)?\s*(class|interface|trait)#', $line)) {
				array_splice($lines, $i + 2, 0, '');

				break;
			}
		}

		array_splice($lines, -2, 0, '');

		return implode("\n", $lines);
	}

}

<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Utility;

use Nette\PhpGenerator\PhpNamespace;

final class UseStatements
{

	/** @var bool[] */
	private array $statements = [];

	public function __construct(
		private PhpNamespace $namespace,
	)
	{
	}

	public function use(string $class, bool $shortName = false): string
	{
		if (!isset($this->statements[$class])) {
			$this->namespace->addUse($class);

			$this->statements[$class] = true;
		}

		return $shortName ? PhpClassNaming::extractClassName($class) : $class;
	}

}

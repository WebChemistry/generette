<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Utility;

use Nette\PhpGenerator\PhpNamespace;

final class UseStatements
{

	private const BUILT_IN = ['string', 'int', 'bool', 'mixed', 'iterable', 'array', 'callable', 'object', 'resource'];

	/** @var bool[] */
	private array $statements = [];

	public function __construct(
		private PhpNamespace $namespace,
	)
	{
	}

	public function use(string $class, bool $shortName = false): string
	{
		if (self::isBuiltIn($class)) {
			return $class;
		}

		if (!isset($this->statements[$class])) {
			$this->namespace->addUse($class);

			$this->statements[$class] = true;
		}

		return $shortName ? PhpClassNaming::extractClassName($class) : $class;
	}

	public static function isBuiltIn(string $type): bool
	{
		return in_array(ltrim($type, '?'), self::BUILT_IN, true);
	}

}

<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Utility;

use Nette\PhpGenerator\Helpers;

final class PhpClassNaming
{

	private string $className;

	private ?string $namespace;

	public function __construct(
		private string $fullName,
	)
	{
		$this->namespace = self::extractNamespace($this->fullName);
		$this->className = self::extractClassName($this->fullName);
	}

	public function getClassName(): string
	{
		return $this->className;
	}

	public function getNamespace(): ?string
	{
		return $this->namespace;
	}

	public function getFullName(): string
	{
		return $this->fullName;
	}

	public function getFileName(): string
	{
		return $this->className . '.php';
	}

	public function withAppendedNamespace(string $append): self
	{
		return new self(self::mergeWithSlash($this->namespace, $append, $this->className));
	}

	public function withClassName(string $className): self
	{
		return new self(self::mergeWithSlash($this->namespace, $className));
	}

	public function withAppendedClassName(string $className): self
	{
		return self::withClassName($this->className . $className);
	}

	public static function extractNamespace(string $fullName): ?string
	{
		$namespace = Helpers::extractNamespace($fullName);

		return $namespace ?: null;
	}

	public static function extractClassName(string $fullName): string
	{
		return Helpers::extractShortName($fullName);
	}

	public static function mergeWithSlash(?string ...$arguments): string
	{
		$str = '';

		foreach ($arguments as $argument) {
			if ($argument) {
				$str .= $argument;
				$str .= '\\';
			}
		}

		return $str ? substr($str, 0, -1) : $str;
	}

}

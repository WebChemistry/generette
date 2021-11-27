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
		$this->fullName = self::normalizeNamespace(strtr($this->fullName, ['/' => '\\']));
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

	public function getPath(): string
	{
		return $this->namespace ? strtr($this->namespace, ['\\' => '/']) : '';
	}

	public function withMapped(callable $mapCallback): self
	{
		return new self(self::mergeWithSlash(...array_map(
			$mapCallback,
			explode('\\', $this->getFullName())
		)));
	}

	public function withNamespace(string $namespace): self
	{
		return new self(self::mergeWithSlash($namespace, $this->className));
	}

	public function withRemovedNamespace(string $namespace): self
	{
		return $this->withNamespace($this->removeFromNamespace($namespace));
	}

	public function withRemovedNamespaceFromStart(string $namespace): self
	{
		return $this->withNamespace($this->removeFromNamespace($namespace, '^'));
	}

	public function withRemovedNamespaceFromEnd(string $namespace): self
	{
		return $this->withNamespace($this->removeFromNamespace($namespace, '', '$'));
	}

	public function withAppendedNamespace(string $append): self
	{
		return new self(self::mergeWithSlash($this->namespace, $append, $this->className));
	}

	public function withPrependedNamespace(string $prepend): self
	{
		return new self(self::mergeWithSlash($prepend, $this->namespace, $this->className));
	}

	public function withClassName(string $className): self
	{
		return new self(self::mergeWithSlash($this->namespace, $className));
	}

	public function withAppendedClassName(string $append, bool $checkDuplication = false): self
	{
		if ($checkDuplication && str_ends_with($this->className, $append)) {
			return $this->withClassName($this->className);
		}

		return $this->withClassName($this->className . $append);
	}

	public function withPrependedClassName(string $prepend, bool $checkDuplication = false): self
	{
		if ($checkDuplication && str_starts_with($this->className, $prepend)) {
			return $this->withClassName($this->className);
		}

		return $this->withClassName($prepend . $this->className);
	}

	public static function createWithMerge(?string ...$arguments): self
	{
		return new self(self::mergeWithSlash(...$arguments));
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

	public static function normalizeNamespace(string $namespace): string
	{
		return trim($namespace, '\\');
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

	private function removeFromNamespace(string $needle, string $regexPrepend = '', string $regexAppend = ''): ?string
	{
		if (!$this->namespace) {
			return $this->namespace;
		}

		$arg = preg_quote(self::normalizeNamespace($needle));
		$pattern = sprintf('#%s\\\\?%s\\\\?%s#', $regexPrepend, $arg, $regexAppend);

		return self::normalizeNamespace(
			preg_replace($pattern, '\\', $this->namespace)
		);
	}

}

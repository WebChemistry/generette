<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Printer;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Dumper;
use Nette\PhpGenerator\Helpers;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use Nette\Utils\Strings;

final class DefaultPrinter extends Printer
{

	public function __construct()
	{
		$this->linesBetweenMethods = 1;
	}

	public function printClass(ClassType $class, PhpNamespace $namespace = null): string
	{
		$class->validate();
		$resolver = $namespace
			? [$namespace, 'unresolveUnionType']
			: function ($s) { return $s; };

		$traits = [];
		foreach ($class->getTraitResolutions() as $trait => $resolutions) {
			$traits[] = 'use ' . $resolver($trait)
				. ($resolutions ? " {\n" . $this->indentation . implode(";\n" . $this->indentation, $resolutions) . ";\n}\n" : ";\n");
		}

		$consts = [];
		foreach ($class->getConstants() as $const) {
			$def = ($const->getVisibility() ? $const->getVisibility() . ' ' : '') . 'const ' . $const->getName() . ' = ';
			$consts[] = Helpers::formatDocComment((string) $const->getComment())
				. self::printAttributes($const->getAttributes(), $namespace)
				. $def
				. $this->dump($const->getValue(), strlen($def)) . ";\n";
		}

		$properties = [];
		foreach ($class->getProperties() as $property) {
			$type = $property->getType();
			$def = (($property->getVisibility() ?: 'public') . ($property->isStatic() ? ' static' : '') . ' '
				. ltrim($this->printType($type, $property->isNullable(), $namespace) . ' ')
				. '$' . $property->getName());

			$properties[] = Helpers::formatDocComment((string) $property->getComment())
				. self::printAttributes($property->getAttributes(), $namespace)
				. $def
				. ($property->getValue() === null && !$property->isInitialized() ? '' : ' = ' . $this->dump($property->getValue(), strlen($def) + 3)) // 3 = ' = '
				. ";\n";
		}

		$methods = [];
		foreach ($class->getMethods() as $method) {
			$methods[] = $this->printMethod($method, $namespace);
		}

		$members = array_filter([
			implode('', $traits),
			$this->joinProperties($consts),
			$this->joinProperties($properties),
			($methods && $properties ? str_repeat("\n", $this->linesBetweenMethods - 1) : '')
			. implode(str_repeat("\n", $this->linesBetweenMethods), $methods),
		]);

		return Strings::normalize(
				Helpers::formatDocComment($class->getComment() . "\n")
				. self::printAttributes($class->getAttributes(), $namespace)
				. ($class->isAbstract() ? 'abstract ' : '')
				. ($class->isFinal() ? 'final ' : '')
				. ($class->getName() ? $class->getType() . ' ' . $class->getName() . ' ' : '')
				. ($class->getExtends() ? 'extends ' . implode(', ', array_map($resolver, (array) $class->getExtends())) . ' ' : '')
				. ($class->getImplements() ? 'implements ' . implode(', ', array_map($resolver, $class->getImplements())) . ' ' : '')
				. ($class->getName() ? "\n" : '') . "{\n"
				. ($members ? "\n" . $this->indent(implode("\n", $members)) . "\n" : "\n")
				. '}'
			) . ($class->getName() ? "\n" : '');
	}

	public function printFile(PhpFile $file): string
	{
		$namespaces = [];
		foreach ($file->getNamespaces() as $namespace) {
			$namespaces[] = $this->printNamespace($namespace);
		}

		return Strings::normalize(
				"<?php " . ($file->hasStrictTypes() ? "declare(strict_types = 1);" : '')
				. ($file->getComment() ? "\n" . Helpers::formatDocComment($file->getComment() . "\n") : '')
				. "\n\n"
				. implode("\n\n", $namespaces)
			) . "\n";
	}

	private function printAttributes(array $attrs, ?PhpNamespace $namespace, bool $inline = false): string
	{
		if (!$attrs) {
			return '';
		}
		$items = [];
		foreach ($attrs as $attr) {
			$args = (new Dumper)->format('...?:', $attr->getArguments());
			$items[] = $this->printType($attr->getName(), false, $namespace) . ($args ? "($args)" : '');
		}
		return $inline
			? '#[' . implode(', ', $items) . '] '
			: '#[' . implode("]\n#[", $items) . "]\n";
	}

	private function joinProperties(array $props)
	{
		return $this->linesBetweenProperties
			? implode(str_repeat("\n", $this->linesBetweenProperties), $props)
			: preg_replace('#^(\w.*\n)\n(?=\w.*;)#m', '$1', implode("\n", $props));
	}

}

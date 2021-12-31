<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Property;

use LogicException;
use Nette\Utils\Strings;
use Nette\Utils\Type;

/**
 * #var - protected property
 * -var - private property
 * +var - public property
 * var:string - type of var is string
 * var,var2 - var and var2 variables
 * var@cs@set - flags @cs and @set
 */
final class PropertyParser
{

	private const PATTERN = /** @lang PhpRegExp */ '~^
		\s*(?<visibility>\#|-|\+)? # visibility private / protected
		\s*(?<name>\w+) # name of variable
		\s*(?::(?<type>[a-zA-Z0-9/?\\\\]+))? # type of variable
		\s*(?:=(?<default>(?:(?:\w+)|(?:"[^"]+?")|(?:\'[^\']+?\'))))? # default value
		\s*(?<flags>@[a-z@\s!]+)? # flags
		$~x';

	public static function parse(string $string): PropertyCollection
	{
		if (!$string) {
			return new PropertyCollection();
		}

		$collection = [];

		foreach (explode(',', $string) as $item) {
			$item = trim($item);

			if (!$item) {
				continue;
			}

			$matches = Strings::match($item, self::PATTERN);
			if (!$matches) {
				throw new LogicException(sprintf('Property %s does not match given pattern.', $item));
			}

			$visibility = match ($matches['visibility'] ?? '') {
				'#' => Property::PROTECTED,
				'-' => Property::PRIVATE,
				'+' => Property::PUBLIC,
				default => '',
			};

			$default = $matches['default'] ?? '';
			$surrounded = false;

			if (str_starts_with($default, '"')) {
				$surrounded = true;
				$default = trim($default, '"');
			} else if (str_starts_with($default, "'")) {
				$surrounded = true;
				$default = trim($default, "'");
			}

			$type = $matches['type'] ?? '';
			$name = $matches['name'] ?? '';
			$flags = array_filter(array_map(
				fn (string $str) => trim($str),
				explode('@', $matches['flags'] ?? ''),
			));

			$collection[$name] = new Property(
				$visibility ?: null,
				$name,
				$type ? Type::fromString(strtr($type, ['/' => '\\'])) : null,
				$default ?: null,
				$surrounded,
				$flags,
			);
		}

		return new PropertyCollection($collection);
	}

}

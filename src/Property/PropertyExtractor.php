<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Property;

use DomainException;
use WebChemistry\Generette\Utility\Result\PropertyExtractedResult;

final class PropertyExtractor
{

	/**
	 * property: int, property2: string
	 * property@flag
	 * property@!flag
	 * property@flag=default
	 * property=default
	 *
	 * @param array<string, bool> $flags
	 * @return PropertyExtractedResult[]
	 */
	public static function extract(?string $string, array $flags, string $visibility): array
	{
		if (!$string) {
			return [];
		}

		$properties = array_map(
			function (string $definition) use ($flags, $visibility): array
			{
				$extractedFlags = [];

				$default = null;

				$definition = preg_replace_callback('#@(!)?([a-zA-Z]+)#', function (array $matches) use (&$extractedFlags, $flags): string {
					$flag = strtolower($matches[2]);
					if (!isset($flags[$flag])) {
						throw new DomainException(sprintf('Unexpected flag "%s".', $flag));
					}

					$extractedFlags[$flag] = $matches[1] !== '!';

					return '';
				}, $definition);

				$definition = preg_replace_callback('#=([a-zA-Z0-9]*)#', function (array $matches) use (&$default): string {
					$default = $matches[1];

					return '';
				}, $definition);

				$explode = explode(':', $definition);

				$name = $explode[0];

				if (str_starts_with($name, '+')) {
					$name = substr($name, 1);
					$visibility = 'public';

				} elseif (str_starts_with($name, '-')) {
					$name = substr($name, 1);
					$visibility = 'private';

				} elseif (str_starts_with($name, '#')) {
					$name = substr($name, 1);
					$visibility = 'protected';

				}

				return [
					'name' => $name,
					'visibility' => $visibility,
					'type' => $explode[1] ?? null,
					'flags' => $extractedFlags,
					'default' => $default,
				];
			},
			array_filter(array_map('trim', explode(',', $string))),
		);

		$return = [];
		foreach ($properties as $map) {
			$return[] = new PropertyExtractedResult(
				trim($map['name']),
				$map['type'] === null ? null : trim($map['type']),
				$map['flags'],
				$flags,
				$map['default'],
				$map['visibility'],
			);
		}

		return $return;
	}

}

<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Utility;

use DomainException;
use WebChemistry\Generette\Utility\Result\PropertyExtractedResult;

final class PropertyExtractor
{

	/**
	 * Extracts property:int,property2:string,property3@flag@!false=default
	 *
	 * @param array<string, bool> $flags
	 * @return PropertyExtractedResult[]
	 */
	public static function extract(?string $string, array $flags): array
	{
		if (!$string) {
			return [];
		}

		$properties = array_map(
			function (string $definition) use ($flags): array
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

				$type = $explode[1] ?? null;

				return [
					'name' => $explode[0],
					'type' => $type,
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
			);
		}

		return $return;
	}

}

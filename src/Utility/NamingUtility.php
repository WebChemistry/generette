<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Utility;

use JetBrains\PhpStorm\ArrayShape;

final class NamingUtility
{

	#[ArrayShape(['string|null', 'string'])]
	public static function splitToNamespaceAndClassName(string $fullName): array
	{
		if (($pos = strrpos($fullName, '\\')) === false) {
			return [null, $fullName];
		}

		return [substr($fullName, 0, $pos), substr($fullName, $pos + 1)];
	}



	public static function splitWithSlash(?string ...$arguments): string
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

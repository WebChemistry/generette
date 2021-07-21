<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Utility;

final class FilePathUtility
{

	public static function join(string ...$paths): string
	{
		$str = '';
		foreach ($paths as $path) {
			if ($path = rtrim($path, '/')) {
				$str .= rtrim($path, '/') . '/';
			}
		}

		return $str ? substr($str, 0, -1) : $str;
	}

}

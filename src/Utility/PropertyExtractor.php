<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Utility;

use LogicException;
use Nette\Utils\Finder;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Utilitte\Php\Strings;
use WebChemistry\Generette\Utility\ValueObject\PropertyExtractedObject;

final class PropertyExtractor
{

	private static array $classNamesCache = [];

	/**
	 * Extracts property:int,property2:string,property3@flag@!false=default
	 * @return PropertyExtractedObject[]
	 */
	public static function extract(
		?string $string,
		OutputInterface $output,
		InputInterface $input,
		QuestionHelper $questionHelper,
		array $suggestionPaths,
	): array
	{
		if (!$string) {
			return [];
		}

		$properties = array_map(
			function (string $definition) use ($output, $input, $questionHelper, $suggestionPaths): array
			{
				$flags = [];
				$default = null;
				$definition = preg_replace_callback('#@(!)?([a-zA-Z]+)#', function (array $matches) use (&$flags): string {
					$flags[strtolower($matches[2])] = $matches[1] !== '!';

					return '';
				}, $definition);
				$definition = preg_replace_callback('#=([a-zA-Z0-9]*)#', function (array $matches) use (&$default): string {
					$default = $matches[1];

					return '';
				}, $definition);

				$explode = explode(':', $definition);

				$type = $explode[1] ?? null;
				if ($type !== null) {
					if (str_contains($type, '|')) {
						throw new LogicException('Union type is not currently supported.');
					}

					if (!UseStatements::isBuiltIn($type)) {
						$type = self::suggestType($type, $output, $input, $questionHelper, $suggestionPaths);
					}
				}

				return [
					'name' => $explode[0],
					'type' => $type,
					'flags' => $flags,
					'default' => $default,
				];
			},
			array_filter(array_map('trim', explode(',', $string))),
		);

		$return = [];
		foreach ($properties as $map) {
			$return[] = new PropertyExtractedObject(
				trim($map['name']),
				$map['type'] === null ? null : trim($map['type']),
				$map['flags'],
				$map['default'],
			);
		}

		return $return;
	}

	private static function suggestType(
		string $type,
		OutputInterface $output,
		InputInterface $input,
		QuestionHelper $questionHelper,
		array $suggestionPaths
	): ?string
	{
		if (!$suggestionPaths) {
			throw new LogicException(
				sprintf('Cannot suggest type %s, suggestionPath is not set, please use addSuggestionPath().', $type)
			);
		}

		[$namespace, $className] = Strings::splitByPositionFalseable($type, strrpos($type, '/'));
		$lower = strtolower($className);
		$namespaceLower = $namespace ? strtolower($namespace) : '';

		foreach ($suggestionPaths as $suggestionPath) {
			self::$classNamesCache[$suggestionPath] ??= self::findClasses($suggestionPath);
			$possibilities = [0 => 'skip'];

			foreach (self::$classNamesCache[$suggestionPath] as $names) {
				if (!str_contains($names['nameLower'], $lower)) {
					continue;
				}
				if ($namespaceLower && !str_contains($names['namespaceLower'], $namespaceLower)) {
					continue;
				}

				$possibilities[] = $names['fullName'];
			}

			if (count($possibilities) > 1) {
				break;
			}
		}

		$choice = new ChoiceQuestion(
			sprintf('Choose type for %s <comment>[0]</comment>: ', $type),
			$possibilities,
			0
		);

		$result = $questionHelper->ask($input, $output, $choice);

		if ($result === 'skip') {
			return null;
		}

		return $result;
	}

	private static function findClasses(string $path): array
	{
		$array = [];
		foreach (ClassFinder::findClasses(Finder::findFiles('*.php')->from($path)) as $className) {
			[$namespace, $class] = Strings::splitByPositionFalseable($className, strrpos($className, '\\'));

			$array[] = [
				'fullName' => $className,
				'namespaceLower' => $namespace ? strtr(strtolower($namespace), ['\\' => '/']) : '',
				'nameLower' => strtolower($class),
			];
		}

		return $array;
	}

}

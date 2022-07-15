<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Property;

use ArrayIterator;
use IteratorAggregate;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use WebChemistry\Generette\Utility\Generette;
use WebChemistry\Generette\Utility\PropertyExtractor;
use WebChemistry\Generette\Utility\PropertyGenerator;
use WebChemistry\Generette\Utility\Result\PropertyExtractedResult;

/**
 * @implements IteratorAggregate<PropertyExtractedResult>
 */
final class Properties implements IteratorAggregate
{

	private const EXAMPLES = [
		'var:int',
	];

	/** @var PropertyExtractedResult[] */
	private array $extracted;

	/**
	 * @param array<string, array{ desc: string, default?: bool }> $flags
	 */
	public function __construct(
		bool $promFlag = true,
		bool $csFlag = false,
		bool $getFlag = false,
		bool $setFlag = false,
		private array $flags = [],
		private string $description = 'Generate properties',
	)
	{
		$this->flags['prom'] = ['desc' => 'generate promoted property', 'default' => $promFlag];
		$this->flags['pub'] = ['desc' => 'generate public property'];
		$this->flags['pr'] = ['desc' => 'generate protected property'];
		$this->flags['cs'] = ['desc' => 'generate property in constructor', 'default' => $csFlag];
		$this->flags['get'] = ['desc' => 'generate getter method', 'default' => $getFlag];
		$this->flags['set'] = ['desc' => 'generate setter method', 'default' => $setFlag];
	}

	public function parse(string $string): self
	{
		$this->extracted = PropertyExtractor::extract(
			$string,
			array_map(fn (array $options): bool => $options['default'] ?? false, $this->flags)
		);

		return $this;
	}

	/**
	 * @return PropertyExtractedResult[]
	 */
	public function toArray(): array
	{
		return $this->extracted;
	}

//	public function initialize(): static
//	{
//		$this->command->addOption(
//			$this->name,
//			$this->shortcut,
//			InputOption::VALUE_REQUIRED,
//			$this->getHelp(),
//		);
//
//		return $this;
//	}

	private function createGenerator(Generette $generette): PropertyGenerator
	{
		return PropertyGenerator::create(
			$this->toArray(),
			$generette,
		);
	}

	public function generateProperties(Generette $generette, ClassType $classType): static
	{
		$this->createGenerator($generette)->generateProperties($classType);

		return $this;
	}

	public function generateConstructor(Generette $generette, Method $method): static
	{
		$this->createGenerator($generette)->generateConstructor($method);

		return $this;
	}

	public function generateGettersAndSetters(Generette $generette, ClassType $classType): static
	{
		$this->createGenerator($generette)->generateGettersAndSetters($classType);

		return $this;
	}

	public function generateAll(Generette $generette, ClassType $classType): static
	{
		if (!$classType->hasMethod('__construct')) {
			$classType->addMethod('__construct');
		}

		$this->generateConstructor($generette, $classType->getMethod('__construct'));
		$this->generateGettersAndSetters($generette, $classType);
		$this->generateProperties($generette, $classType);

		return $this;
	}

	public function getDescription(): string
	{
		$examples = '';
		foreach (self::EXAMPLES as $example) {
			$examples .= sprintf("<comment>\t</comment>%s\n", $example);
		}
		$examples = substr($examples, 0, -1);

		$flags = '';
		foreach ($this->flags as $name => $options) {
			$default = $options['default'] ?? false;

			$flags .= sprintf("<comment>\t</comment>@%s - %s ", $name, $options['desc'] ?? '');
			if ($default) {
				$flags .= sprintf('(@!%s negates) ', $name);
			}

			$flags .= sprintf("<comment>[%s]</comment>\n", $default ? 'yes' : 'no');
		}

		$flags = $flags ? substr($flags, 0, -1) : '';

		return sprintf(
			"%s.\n"
			. "<comment>Examples</comment>\n%s\n"
			. "<comment>Flags</comment>\n%s",
			rtrim($this->description, '.'),
			$examples,
			$flags,
		);

		return strtr($template, [
			'{{description}}' => $this->description,
			'{{flags}}' => $flags,
		]);
	}

	/**
	 * @return ArrayIterator<PropertyExtractedResult>
	 */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->extracted);
	}

}

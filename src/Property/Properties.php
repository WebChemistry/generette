<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Property;

use ArrayIterator;
use IteratorAggregate;
use Nette\PhpGenerator\ClassType;
use WebChemistry\Generette\Utility\Generette;
use WebChemistry\Generette\Utility\Result\PropertyExtractedResult;

/**
 * @implements IteratorAggregate<PropertyExtractedResult>
 */
final class Properties implements IteratorAggregate
{

	private const SPACE = "<comment>\t</comment>";

	public const FLAG_CS = ['cs' => ['desc' => 'generate promoted property', 'default' => false]];
	public const FLAG_CS_TRUE = ['cs' => ['desc' => 'generate promoted property', 'default' => true]];

	public const FLAG_GET = ['get' => ['desc' => 'generate getter method', 'default' => false]];
	public const FLAG_GET_TRUE = ['get' => ['desc' => 'generate getter method', 'default' => true]];

	public const FLAG_SET = ['set' => ['desc' => 'generate setter method', 'default' => false]];
	public const FLAG_SET_TRUE = ['set' => ['desc' => 'generate setter method', 'default' => true]];

	public const FLAG_PROM = ['prom' => ['desc' => 'generate promoted property', 'default' => false]];
	public const FLAG_PROM_TRUE = ['prom' => ['desc' => 'generate promoted property', 'default' => true]];

	private const EXAMPLES = [
		'variable-name:php-type@flag=default-value',
		'variable:string@get=val',
		'variable1, variable2',
		'variable=default',
	];

	private const VISIBILITY = [
		'+var' => 'public',
		'#var' => 'protected',
		'-var' => 'private',
	];

	/** @var PropertyExtractedResult[] */
	private array $extracted;

	/**
	 * @param array<string, array{ desc: string, default?: bool }> $flags
	 */
	public function __construct(
		private array $flags = [],
		private string $description = 'Generate properties',
		private string $visibility = 'private',
	)
	{
	}

	public function parse(string $string): self
	{
		$this->extracted = PropertyExtractor::extract(
			$string,
			array_map(fn (array $options): bool => $options['default'] ?? false, $this->flags),
			$this->visibility,
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

	/**
	 * @return array<string, PropertyExtractedResult>
	 */
	public function toIndexedArray(): array
	{
		$return = [];

		foreach ($this->extracted as $result) {
			$return[$result->getName()] = $result;
		}

		return $return;
	}

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

	public function generateConstructor(Generette $generette, ClassType $class): static
	{
		$this->createGenerator($generette)->generateConstructor(classType: $class);

		return $this;
	}

	public function generateGettersAndSetters(Generette $generette, ClassType $classType): static
	{
		$this->createGenerator($generette)->generateGettersAndSetters($classType);

		return $this;
	}

	public function generate(Generette $generette, ClassType $classType): static
	{
		$this->generateConstructor($generette, $classType);
		$this->generateGettersAndSetters($generette, $classType);
		$this->generateProperties($generette, $classType);

		return $this;
	}

	public function getDescription(): string
	{
		$description = $this->description . "\n";

		$description .= $this->comment("Visibility\n");
		foreach (self::VISIBILITY as $example => $visibility) {
			$description .= self::SPACE . $example . ' is ' . $visibility;

			if ($visibility === $this->visibility) {
				$description .= ' ' . $this->comment('[default]');
			}

			$description .= "\n";
		}

		if ($this->flags) {
			$description .= $this->comment("Flags\n");
			foreach ($this->flags as $name => $options) {
				$default = $options['default'] ?? false;

				$description .= self::SPACE . '@' . $name . ' - ' . ($options['desc'] ?? '') . ' ';

				if ($default) {
					$description .= sprintf('(@!%s negates) ', $name);
				}

				$description .= $this->comment(sprintf('[%s]', $default ? 'yes' : 'no'));

				$description .= "\n";
			}
		}

		$description .= $this->comment("Examples\n");

		foreach (self::EXAMPLES as $example) {
			$description .= self::SPACE . $example . "\n";
		}

		return rtrim($description);
	}

	private function comment(string $comment): string
	{
		return sprintf('<comment>%s</comment>', $comment);
	}

	/**
	 * @return ArrayIterator<PropertyExtractedResult>
	 */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->extracted);
	}

}

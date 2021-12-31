<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Property;

use Nette\PhpGenerator\ClassType;
use Nette\Utils\Type;
use Symfony\Component\Console\Input\InputOption;
use WebChemistry\ConsoleArguments\BaseCommand;
use function Clue\StreamFilter\remove;

final class PropertyOption
{

	private const HELP = "%s
<comment>Examples:</comment>
	var:int,var:string
	var@flag
	var=default
<comment>Flags:</comment>
	%s";

	private ?string $visibility = null;

	private ?Type $type = null;

	private ?bool $constructorProperties = null;

	private ?bool $promotedProperties = null;

	private ?bool $getterProperties = null;

	private ?bool $setterProperties = null;

	/** @var array<string, string> */
	private $flagsHelp = [];

	/** @var array<string, bool> */
	private $flags = [];

	public function __construct(
		private BaseCommand $command,
		private string $name = 'properties',
		private ?string $shortcut = null,
		private string $description = 'Generates properties',
	)
	{
	}

	public function setVisibility(?string $visibility): self
	{
		$this->visibility = $visibility;

		return $this;
	}

	public function setType(?Type $type): self
	{
		$this->type = $type;

		return $this;
	}

	public function addConstructorProperties(bool $default): self
	{
		$this->constructorProperties = $default;
		$this->addFlag('cs', $default);

		return $this;
	}

	public function addPromotedProperties(bool $default): self
	{
		$this->promotedProperties = $default;
		$this->addFlag('pr', $default);

		return $this;
	}

	public function addGetterProperties(bool $default): self
	{
		$this->getterProperties = $default;
		$this->addFlag('get', $default);

		return $this;
	}

	public function addSetterProperties(bool $default): self
	{
		$this->setterProperties = $default;
		$this->addFlag('set', $default);

		return $this;
	}

	public function initialize(): self
	{
		$this->command->addOption(
			$this->name,
			$this->shortcut,
			InputOption::VALUE_REQUIRED,
			$this->getHelp(),
		);

		return $this;
	}

	public function getProperties(): PropertyCollection
	{
		$input = $this->command->getInput()->getOption($this->name);

		return PropertyParser::parse((string) $input)
			->withDefaults(
				$this->visibility,
				$this->type,
				$this->flags,
			);
	}

	public function getGenerator(): PropertyGenerator
	{
		return new PropertyGenerator($this->getProperties());
	}

	public function generate(ClassType $classType): static
	{
		$created = false;
		if (!$classType->hasMethod('__construct')) {
			$created = true;

			$classType->addMethod('__construct');
		}

		$generator = $this->getGenerator();
		$used = $generator->generateConstructor($classType->getMethod('__construct'));
		$generator->generateGettersAndSetters($classType);
		$generator->generateProperties($classType);

		if ($created && !$used) {
			$classType->removeMethod('__construct');
		}

		return $this;
	}

	public function addFlag(string $flag, bool $default, ?string $help = null): self
	{
		$this->flags[$flag] = $default;
		$this->flagsHelp[$flag] = sprintf(
			'@%s - %s%s <comment>[%s]</comment>',
			$flag,
			$help ? $help . ' ' : '',
			sprintf('(@!%s - don\'t generate)', $flag),
			$default ? 'yes' : 'no',
		);

		return $this;
	}

	private function getHelp(): string
	{
		return sprintf(self::HELP, $this->description, implode("\n", $this->flagsHelp));
	}

}

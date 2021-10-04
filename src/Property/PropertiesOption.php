<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Property;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Symfony\Component\Console\Input\InputOption;
use WebChemistry\ConsoleArguments\BaseCommand;
use WebChemistry\Generette\Utility\PropertyExtractor;
use WebChemistry\Generette\Utility\PropertyGenerator;
use WebChemistry\Generette\Utility\UseStatements;
use WebChemistry\Generette\Utility\ValueObject\PropertyExtractedObject;

final class PropertiesOption
{

	private array $suggestionPaths = [];

	private bool $promotedFlag = true;

	private bool $constructorFlag = false;

	private bool $getterFlag = false;

	private bool $setterFlag = false;

	private PropertyGenerator $generator;

	private UseStatements $useStatements;

	/** @var array<string, string> */
	private array $flags = [];

	public function __construct(
		private BaseCommand $command,
		private string $name = 'properties',
		private ?string $shortcut = null,
		private string $description = 'Generate properties',
	)
	{
	}

	public function setPromotedFlag(bool $promotedFlag): static
	{
		$this->promotedFlag = $promotedFlag;

		return $this;
	}

	public function setConstructorFlag(bool $constructorFlag): static
	{
		$this->constructorFlag = $constructorFlag;

		return $this;
	}

	public function setGetterFlag(bool $getterFlag): static
	{
		$this->getterFlag = $getterFlag;

		return $this;
	}

	public function setSetterFlag(bool $setterFlag): static
	{
		$this->setterFlag = $setterFlag;

		return $this;
	}

	public function setSuggestionPaths(array $suggestionPaths): static
	{
		$this->suggestionPaths = $suggestionPaths;

		return $this;
	}

	public function setUseStatements(UseStatements $useStatements): static
	{
		$this->useStatements = $useStatements;

		return $this;
	}

	public function addFlag(string $name, string $description): static
	{
		$this->flags[$name] = $description;

		return $this;
	}

	/**
	 * @return PropertyExtractedObject[]
	 */
	public function getAll(): array
	{
		return PropertyExtractor::extract(
			$this->command->getInput()->getOption($this->name),
			$this->command->getOutput(),
			$this->command->getInput(),
			$this->command->getHelper('question'),
			$this->suggestionPaths,
		);
	}

	public function initialize(): static
	{
		$this->command->addOption(
			$this->name,
			$this->shortcut,
			InputOption::VALUE_REQUIRED,
			$this->getHelp(),
		);

		return $this;
	}

	private function getGenerator(): PropertyGenerator
	{
		return $this->generator ??= PropertyGenerator::create(
			$this->getAll(),
			$this->useStatements,
			$this->promotedFlag
		);
	}

	public function generateProperties(ClassType $classType): static
	{
		$this->getGenerator()->generateProperties($classType, $this->constructorFlag);

		return $this;
	}

	public function generateConstructor(Method $method): static
	{
		$this->getGenerator()->generateConstructor($method, $this->constructorFlag);

		return $this;
	}

	public function generateGettersAndSetters(ClassType $classType): static
	{
		$this->getGenerator()->generateGettersAndSetters($classType, $this->getterFlag, $this->setterFlag);

		return $this;
	}

	private function getHelp(): string
	{
		$template = file_get_contents(__DIR__ . '/templates/help.tmpl');

		$flags = '';
		foreach ($this->flags as $name => $description) {
			$flags .= sprintf("@%s - %s\n", $name, $description);
		}
		$flags = $flags ? substr($flags, 0, -1) : '';

		return strtr($template, [
			'{{description}}' => $this->description,
			'{{cs}}' => $this->constructorFlag ? 'yes' : 'no',
			'{{get}}' => $this->getterFlag ? 'yes' : 'no',
			'{{set}}' => $this->setterFlag ? 'yes' : 'no',
			'{{promoted}}' => $this->promotedFlag ? 'yes' : 'no',
			'{{flags}}' => $flags,
		]);
	}

}

<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Utility;

use DomainException;
use JetBrains\PhpStorm\Immutable;
use Nette\PhpGenerator\ClassLike;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\InterfaceType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WebChemistry\Generette\Composer\ComposerPathAutoload;
use WebChemistry\Generette\Printer\DefaultPrinter;

final class Generette
{

	#[Immutable]
	public FilesWriter $filesWriter;

	#[Immutable(Immutable::PRIVATE_WRITE_SCOPE)]
	public ?PhpNamespace $namespace = null;

	private Printer $printer;

	public function __construct(
		private InputInterface $input,
		private OutputInterface $output,
		private Command $command,
		private ?ComposerPathAutoload $composer,
		?Printer $printer = null,
	)
	{
		$this->filesWriter = new FilesWriter($this->input, $this->output, $this->command->getHelper('question'));
		$this->printer = $printer ?? new DefaultPrinter();
	}

	public function setPrinter(Printer $printer): static
	{
		$this->printer = $printer;

		return $this;
	}

	public function createClassName(string $className, ?string $namespacePrefix): PhpClassName
	{
		$name = new PhpClassNameWithNamespacePrefix($className);
		$name = $name->withMap(
			fn (string $token) => ucfirst($token),
		);

		if (
			$namespacePrefix &&
			!str_starts_with($className, '\\') &&
			!str_starts_with($className, '/')
		) {
			$name->setNamespacePrefix($namespacePrefix);
			$name = $name->withPrependedNamespace($namespacePrefix);
		}

		return $name;
	}

	public function createInterfaceType(PhpClassName $className, ?string $baseDir = null): InterfaceType
	{
		$this->processClassLike($interface = new InterfaceType($className->getClassName()), $className, $baseDir);

		return $interface;
	}

	public function createClassType(PhpClassName $className, ?string $baseDir = null): ClassType
	{
		$this->processClassLike($class = new ClassType($className->getClassName()), $className, $baseDir);

		return $class;
	}

	private function processClassLike(ClassLike $classLike, PhpClassName $className, ?string $baseDir = null): void
	{
		$file = new PhpFile();
		$file->setStrictTypes();

		if ($className instanceof PhpClassNameWithNamespacePrefix) {
			$path = $this->getFilePathFromClassName($className, $baseDir, $className->getNamespacePrefix());
		} else {
			$path = $this->getFilePathFromClassName($className, $baseDir);
		}

		if (!$baseDir && !$this->composer) {
			throw new DomainException('Composer path (recommended) via $command->setComposerDir(...) or baseDir must be set.');
		}

		$this->filesWriter->addLazyFile($path, fn (): string => $this->printer->printFile($file));

		$this->namespace = $file->addNamespace($className->getNamespace());

		$this->namespace->add($classLike);
	}

	public function getFilePathFromClassName(
		PhpClassName $className,
		?string $baseDir = null,
		?string $baseNamespace = null
	): string
	{
		if (!$baseDir) {
			if (!$this->composer) {
				throw new DomainException('Composer path (recommended) via $command->setComposerDir(...) or base path must be set.');
			}

			return $this->composer->resolvePathByPsr4Namespace($className->getNamespace()) . '/' . $className->getFileName();
		}

		if ($baseNamespace) {
			$className = $className->withRemovedNamespaceFromStart($baseNamespace);
		}

		return (new FilePath($baseDir, $className->getPath()))->withAppendedPath($className->getFileName())
			->toString();
	}

	public function finish(bool $overwriteFiles = false): void
	{
		$this->filesWriter->write($overwriteFiles);
	}

	public function use(string $className, bool $fullName = true): string
	{
		if (isset($this->namespace)) {
			$this->namespace->addUse($className);

			return $fullName ? $className : $this->namespace->simplifyType($className);
		}

		return $className;
	}

}

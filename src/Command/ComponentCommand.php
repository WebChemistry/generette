<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command;

use WebChemistry\Generette\Printer\DefaultPrinter;
use WebChemistry\Generette\UI\Control;
use WebChemistry\Generette\UI\DefaultTemplate;
use WebChemistry\Generette\Utility\FilePathUtility;
use WebChemistry\Generette\Utility\NamingUtility;
use WebChemistry\Generette\Utility\PhpClassNaming;
use WebChemistry\Generette\Utility\UseStatements;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\Printer;
use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WebChemistry\ServiceAttribute\Attribute\Service;

final class ComponentCommand extends Command
{

	public static $defaultName = 'generate:component';

	private Printer $printer;

	private UseStatements $useStatements;

	public function __construct(
		private string $basePath,
		private string $namespace,
		private string $controlClass = Control::class,
		private string $templateClass = DefaultTemplate::class,
	)
	{
		$this->printer = new DefaultPrinter();

		parent::__construct();
	}

	protected function configure(): void
	{
		$this->setDescription('Creates new component')
			->addArgument('name', InputArgument::REQUIRED, 'The name of component')
			->addOption('overwrite', 'o')
			->addOption('constructor', 'c');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$baseDir = dirname($input->getArgument('name'));
		$argumentName = strtr($input->getArgument('name'), ['/' => '\\']);
		$overwrite = $input->getOption('overwrite');
		$constructor = $input->getOption('constructor');

		if (!str_ends_with($argumentName, 'Component')) {
			$argumentName .= 'Component';
		}

		$className = new PhpClassNaming(NamingUtility::splitWithSlash($this->namespace, $argumentName));
		$templateName = $this->extractTemplateName($className->getClassName());
		$templateClassName = $className->withAppendedNamespace('Template')->withAppendedClassName('Template');
		$factoryClassName = $className->withAppendedClassName('Factory');

		// validate
		if (!$overwrite && class_exists($className->getFullName())) {
			$output->writeln(sprintf('<error>Class %s already exists.</error>', $className->getFullName()));

			return self::FAILURE;
		}

		if (!$overwrite && class_exists($templateClassName->getFullName())) {
			$output->writeln(sprintf('<error>Class %s already exists.</error>', $templateClassName->getFullName()));

			return self::FAILURE;
		}

		// component file
		$file = $this->createPhpFile();
		$namespace = $file->addNamespace($className->getNamespace());
		$this->useStatements = new UseStatements($namespace);
		$this->processComponentClass($class = $namespace->addClass($className->getClassName()));
		if ($constructor) {
			$class->addMethod('__construct');
		}
		$this->processComponentRenderMethod($class->addMethod('render'), $templateName, $templateClassName->getFullName());

		// template file
		$templateFile = $this->createPhpFile();
		$namespace = $templateFile->addNamespace($templateClassName->getNamespace());
		$this->useStatements = new UseStatements($namespace);
		$this->processTemplateClass($class = $namespace->addClass($templateClassName->getClassName()));

		// factory file
		$factoryFile = $this->createPhpFile();
		$namespace = $factoryFile->addNamespace($factoryClassName->getNamespace());
		$this->useStatements = new UseStatements($namespace);
		$this->processFactoryClass($class = $namespace->addInterface($factoryClassName->getClassName()), $className->getFullName());

		// directories
		FileSystem::createDir($baseDir = FilePathUtility::join($this->basePath, $baseDir));
		FileSystem::createDir($latteDir = FilePathUtility::join($baseDir, 'templates'));
		FileSystem::createDir($templateDir = FilePathUtility::join($baseDir, 'Template'));

		file_put_contents(FilePathUtility::join($baseDir, $className->getFileName()), $this->printer->printFile($file));
		file_put_contents(
			FilePathUtility::join($templateDir, $templateClassName->getFileName()),
			$this->printer->printFile($templateFile)
		);
		file_put_contents(
			FilePathUtility::join($latteDir, $templateName),
			sprintf("{templateType %s}\n", $templateClassName->getFullName())
		);

		file_put_contents(
			FilePathUtility::join($baseDir, $factoryClassName->getFileName()),
			$this->printer->printFile($factoryFile)
		);

		return self::SUCCESS;
	}

	private function createPhpFile(): PhpFile
	{
		$file = new PhpFile();
		$file->setStrictTypes();

		return $file;
	}

	private function processComponentClass(ClassType $class): void
	{
		$class->addExtend($this->useStatements->use($this->controlClass));
		$class->setFinal();
	}

	private function processComponentRenderMethod(Method $method, string $templateName, string $templateClassName): void
	{
		$method->setReturnType('void');

		$method->addBody(sprintf(
			'$template = $this->getTemplateObject(%s::class);',
			$this->useStatements->use($templateClassName, true),
		));
		$method->addBody(sprintf("\$template->setFile(__DIR__ . '/templates/%s');", $templateName));
		$method->addBody("\n");
		$method->addBody('$template->render();');
	}

	private function processTemplateClass(ClassType $class): void
	{
		$class->setFinal();
		$class->addExtend($this->useStatements->use($this->templateClass));
	}

	private function extractTemplateName(string $className): string
	{
		return Strings::firstLower(preg_replace('#Component$#', '', $className)) . '.latte';
	}

	private function processFactoryClass(ClassType $class, string $componentClassName): void
	{
		if (class_exists(Service::class)) {
			$class->addAttribute($this->useStatements->use(Service::class));
		}

		$class->addMethod('create')
			->setVisibility('public')
			->setReturnType($this->useStatements->use($componentClassName));
	}

}

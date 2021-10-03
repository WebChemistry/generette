<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use WebChemistry\Generette\Command\Argument\ComponentArguments;
use WebChemistry\Generette\Printer\DefaultPrinter;
use WebChemistry\Generette\UI\Control;
use WebChemistry\Generette\UI\DefaultTemplate;
use WebChemistry\Generette\Utility\FilePath;
use WebChemistry\Generette\Utility\FilePathUtility;
use WebChemistry\Generette\Utility\FilesWriter;
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

final class ComponentCommand extends GenerateCommand
{

	public static $defaultName = 'make:component';

	protected ComponentArguments $arguments;

	public function __construct(
		private string $basePath,
		private string $namespace,
		private string $controlClass = Control::class,
		private string $templateClass = DefaultTemplate::class,
	)
	{
		parent::__construct();
	}

	protected function exec(): void
	{
		$baseClassName = $this->createClassName($this->arguments->name);
		$className = $baseClassName->withAppendedNamespace($this->namespace)->withAppendedClassName('Component', true);
		$templateName = $this->extractTemplateName($className->getClassName());
		$templateClassName = $className->withAppendedNamespace('Template')->withAppendedClassName('Template');
		$factoryClassName = $className->withAppendedClassName('Factory');

		// component file
		$file = $this->createPhpFile();
		$namespace = $file->addNamespace($className->getNamespace());
		$this->useStatements = new UseStatements($namespace);
		$this->processComponentClass($class = $namespace->addClass($className->getClassName()));
		if ($this->arguments->constructor) {
			$class->addMethod('__construct');
		}
		$this->processComponentRenderMethod($class->addMethod('render'), $templateName, $templateClassName->getFullName());

		// template file
		$templateFile = $this->createPhpFile();
		$namespace = $templateFile->addNamespace($templateClassName->getNamespace());
		$this->useStatements = new UseStatements($namespace);
		$this->processTemplateClass($namespace->addClass($templateClassName->getClassName()));

		// factory file
		$factoryFile = $this->createPhpFile();
		$namespace = $factoryFile->addNamespace($factoryClassName->getNamespace());
		$this->useStatements = new UseStatements($namespace);
		$this->processFactoryClass($namespace->addInterface($factoryClassName->getClassName()), $className->getFullName());

		// directories
		$baseDir = new FilePath($this->basePath, $baseClassName->getPath());
		$latteDir = $baseDir->withAppendedPath('templates');
		$templateDir = $baseDir->withAppendedPath('Template');

		$this->createFilesWriter()
			->addFile(
				$baseDir->withAppendedPath($className->getFileName())->toString(),
				$this->printer->printFile($file),
			)
			->addFile(
				$templateDir->withAppendedPath($templateClassName->getFileName())->toString(),
				$this->printer->printFile($templateFile),
			)
			->addFile(
				$latteDir->withAppendedPath($templateName)->toString(),
				sprintf("{templateType %s}\n", $templateClassName->getFullName()),
			)
			->addFile(
				$baseDir->withAppendedPath($factoryClassName->getFileName())->toString(),
				$this->printer->printFile($factoryFile),
			)
			->write();
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

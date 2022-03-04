<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command;

use Nette\Application\UI\Control;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use WebChemistry\Generette\Command\Argument\ComponentArguments;
use WebChemistry\Generette\Printer\DefaultPrinter;
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
		private string $namespace,
		private string $controlClass = Control::class,
		private string $templateClass = DefaultTemplate::class,
		private string $templateMethod = '$this->getTemplate()',
		private array $traits = [],
		private ?string $basePath = null,
	)
	{
		parent::__construct();
	}

	protected function exec(): void
	{
		$writer = $this->createFilesWriter();

		$className = $this->createClassNameFromArguments($this->arguments, $this->namespace)
			->withAppendedClassName('Component', true);
		$templateName = $this->extractTemplateName($className->getClassName());
		$templateClassName = $className->withAppendedNamespace('Template')->withAppendedClassName('Template');
		$factoryClassName = $className->withAppendedClassName('Factory');

		// component
		$class = $this->createClassFromClassName($file = $this->createPhpFile(), $className);
		$class->addMethod('__construct');
		$this->processComponentClass($class);
		$this->processComponentRenderMethod($class->addMethod('render'), $templateName, $templateClassName->getFullName());

		$writer->addFile($this->getFilePathFromClassName($className), $this->printer->printFile($file));

		// template
		$class = $this->createClassFromClassName($file = $this->createPhpFile(), $templateClassName);
		$this->processTemplateClass($class, $className->getFullName());

		$writer->addFile($this->getFilePathFromClassName($templateClassName), $this->printer->printFile($file));

		// factory
		$interface = $this->createInterfaceFromClassName($file = $this->createPhpFile(), $factoryClassName);
		$this->processFactoryClass($interface, $className->getFullName());

		$writer->addFile($this->getFilePathFromClassName($factoryClassName), $this->printer->printFile($file));

		// latte file
		$writer->addFile(
			dirname($this->getFilePathFromClassName($className)) . '/templates/' . $templateName,
			sprintf("{templateType %s}\n", $templateClassName->getFullName())
		);

		$writer->write();
	}

	private function processComponentClass(ClassType $class): void
	{
		$class->addExtend($this->useStatements->use($this->controlClass));
		$class->setFinal();

		foreach ($this->traits as $trait) {
			$class->addTrait($this->useStatements->use($trait));
		}
	}

	private function processComponentRenderMethod(Method $method, string $templateName, string $templateClassName): void
	{
		$method->setReturnType('void');

		$method->addBody(
			'$template = ' . strtr($this->templateMethod, ['$templateClassName' => $this->useStatements->use($templateClassName, true)])
		);
		$method->addBody("\n");
		$method->addBody(sprintf("\$template->render(__DIR__ . '/templates/%s');", $templateName));
	}

	private function processTemplateClass(ClassType $class, string $controlClass): void
	{
		$class->setFinal();
		$class->addExtend($this->useStatements->use($this->templateClass));

		$class->addProperty('control')
			->setPublic()
			->setType($this->useStatements->use($controlClass));
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

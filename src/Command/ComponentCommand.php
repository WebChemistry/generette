<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command;

use Nette\Application\UI\Control;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\InterfaceType;
use Nette\PhpGenerator\Method;
use Nette\Utils\Strings;
use WebChemistry\Generette\Command\Argument\ComponentArguments;
use WebChemistry\ServiceAttribute\Attribute\Service;

final class ComponentCommand extends GeneretteCommand
{

	public static $defaultName = 'make:component';

	protected ComponentArguments $arguments;

	public function __construct(
		private string $namespace,
		private string $controlClass = Control::class,
		private string $templateClass = Template::class,
		private array $traits = [],
	)
	{
		parent::__construct();
	}

	protected function exec(): void
	{
		$controlClassName = $this->generette->createClassName($this->arguments->name, $this->namespace)
			->withAppendedClassName('Component', true);
		$templateClassName = $controlClassName->withAppendedNamespace('Template')->withAppendedClassName('Template');
		$factoryClassName = $controlClassName->withAppendedClassName('Factory');

		$latteFileName = $this->extractTemplateName($controlClassName->getClassName());

		// control
		$class = $this->generette->createClassType($controlClassName);

		if ($this->arguments->constructor) {
			$class->addMethod('__construct');
		}

		$this->processComponentRenderMethod($class->addMethod('render'), $latteFileName);
		$this->processComponentClass($class, $templateClassName->getFullName());

		// template
		$this->processTemplateClass(
			$this->generette->createClassType($templateClassName),
			$controlClassName->getFullName()
		);

		// factory
		$this->processFactoryInterface(
			$this->generette->createInterfaceType($factoryClassName),
			$controlClassName->getFullName(),
		);

		// latte file
		$this->generette->filesWriter->addFile(
			dirname($this->generette->getFilePathFromClassName($controlClassName)) . '/templates/' . $latteFileName,
			sprintf("{templateType %s}\n", $templateClassName->getFullName())
		);

		$this->generette->finish();
	}

	private function processComponentClass(ClassType $class, string $templateClass): void
	{
		$class->addComment(sprintf('@method %s getTemplate()', $this->generette->use($templateClass, false)));
		$class->addComment(sprintf('@method %s createTemplate()', $this->generette->use($templateClass, false)));
		$class->setExtends($this->generette->use($this->controlClass));
		$class->setFinal();

		foreach ($this->traits as $trait) {
			$class->addTrait($this->generette->use($trait));
		}

		$class->addMethod('formatTemplateClass')
			->setReturnType('string')
			->addBody(sprintf('return %s::class;', $this->generette->use($templateClass, false)));
	}

	private function processComponentRenderMethod(Method $method, string $templateName): void
	{
		$method->setReturnType('void');

		$method->addBody('$template = $this->getTemplate();');
		$method->addBody("\n");
		$method->addBody(sprintf("\$template->render(__DIR__ . '/templates/%s');", $templateName));
	}

	private function processTemplateClass(ClassType $class, string $controlClass): void
	{
		$class->setFinal();
		$class->setExtends($this->generette->use($this->templateClass));

		$class->addProperty('control')
			->setPublic()
			->setType($this->generette->use($controlClass));
	}

	private function extractTemplateName(string $className): string
	{
		return Strings::firstLower(preg_replace('#Component$#', '', $className)) . '.latte';
	}

	private function processFactoryInterface(InterfaceType $interface, string $componentClassName): void
	{
		if (class_exists(Service::class)) {
			$interface->addAttribute($this->generette->use(Service::class));
		}

		$interface->addMethod('create')
			->setVisibility('public')
			->setReturnType($this->generette->use($componentClassName));
	}

}

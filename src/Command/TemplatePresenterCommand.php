<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command;

use WebChemistry\Generette\Command\Argument\TemplatePresenterArguments;

final class TemplatePresenterCommand extends GenerateCommand
{

	protected static $defaultName = 'make:template:presenter';

	protected TemplatePresenterArguments $arguments;

	private string $templateNamespace;

	public function __construct(
		private string $presenterNamespace,
		?string $templateNamespace,
	)
	{
		$this->templateNamespace = $templateNamespace ?? $this->presenterNamespace . '\\Template';
	}

	protected function exec(): void
	{
		$writer = $this->createFilesWriter();
		$className = $this->createClassNameFromArguments($this->arguments, $this->templateNamespace);
		var_dump($className->getClassName());
	}

}

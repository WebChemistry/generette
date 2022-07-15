<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command;

use Nette\PhpGenerator\ClassType;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use WebChemistry\Generette\Command\Argument\MessengerArguments;
use WebChemistry\ServiceAttribute\Attribute\Service;

final class MessengerCommand extends GeneretteCommand
{

	public static $defaultName = 'make:messenger';

	protected MessengerArguments $arguments;

	public function __construct(
		private string $namespace,
		private string $interface = MessageHandlerInterface::class,
	)
	{
		parent::__construct();
	}

	protected function exec(): void
	{
		$className = $this->generette->createClassName($this->arguments->name, $this->namespace);
		$handlerClassName = $className->withAppendedNamespace('Handler')->withAppendedClassName('Handler');

		// message class
		$this->processMessageClass($messageClass = $this->generette->createClassType($className));

		$this->arguments->props?->generateAll($this->generette, $messageClass);

		// handler class
		$this->processHandlerClass($this->generette->createClassType($handlerClassName), $className->getFullName());

		$this->generette->finish();
	}

	private function processMessageClass(ClassType $class): void
	{
		$class->setFinal();
	}

	private function processHandlerClass(ClassType $class, string $parameterClassName): void
	{
		$class->addImplement($this->generette->use($this->interface));
		$class->setFinal();

		if (class_exists(Service::class)) {
			$class->addAttribute($this->generette->use(Service::class));
		}

		$class->addMethod('__construct');

		$invoke = $class->addMethod('__invoke');
		$invoke->setReturnType('void');
		$invoke->addParameter('data')
			->setType($this->generette->use($parameterClassName));
	}
}

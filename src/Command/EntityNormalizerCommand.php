<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command;

use Nette\PhpGenerator\ClassType;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use WebChemistry\Generette\Command\Argument\EntityNormalizerArguments;
use WebChemistry\Generette\Utility\FilePath;
use WebChemistry\Serializer\Guard\SerializerRecursionGuard;
use WebChemistry\ServiceAttribute\Attribute\Service;

final class EntityNormalizerCommand extends GenerateCommand
{

	public static $defaultName = 'make:normalizer:entity';

	protected EntityNormalizerArguments $arguments;

	public function __construct(
		private string $basePath,
		private string $namespace,
	)
	{
		parent::__construct();
	}

	protected function configure()
	{
		parent::configure();
	}

	protected function exec(): void
	{
		$baseClassName = $this->createClassName($this->arguments->name);

		$className = $baseClassName->withPrependedNamespace($this->namespace)->withAppendedClassName('Normalizer', true);

		// normalizer
		$file = $this->createPhpFile();
		$class = $this->createNamespaceFromFile($file, $className->getNamespace())->addClass($className->getClassName());
		$this->processClass($class);

		// directories
		$baseDir = new FilePath($this->basePath, $baseClassName->getPath());

		$this->createFilesWriter()
			->addFile(
				$baseDir->withAppendedPath($className->getFileName())->toString(),
				$this->printer->printFile($file)
			)
			->write();
	}

	public function processClass(ClassType $class): void
	{
		$populateClassName = $this->arguments->populate ? $this->createClassName($this->arguments->populate) : null;

		$class->setFinal();
		$class->addTrait($this->useStatements->use(SerializerRecursionGuard::class));
		if ($this->arguments->constructor) {
			$class->addMethod('__construct');
		}

		if (class_exists(Service::class)) {
			$class->addAttribute($this->useStatements->use(Service::class));
		}

		if ($this->arguments->denormalizer) {
			$class->addImplement($this->useStatements->use(ContextAwareDenormalizerInterface::class));
			$class->addImplement($this->useStatements->use(DenormalizerAwareInterface::class));
			$class->addTrait($this->useStatements->use(DenormalizerAwareTrait::class));

			// denormalize method
			$method = $class->addMethod('denormalize');
			$method->addParameter('data')->setType('mixed');
			$method->addParameter('type')->setType('string');
			$method->addParameter('format')->setType('?string')->setDefaultValue(null);
			$method->addParameter('context')->setType('array')->setDefaultValue([]);

			$method->addComment('@param mixed[] $context');

			// body
			if ($populateClassName) {
				$method->addBody(sprintf('/** @var %s|null $object */', $this->useStatements->use($populateClassName->getFullName(), true)));
				$method->addBody(
					sprintf(
						'$object = $context[%s::OBJECT_TO_POPULATE] ?? null;',
						$this->useStatements->use(AbstractNormalizer::class, true)
					)
				);
			}
			$method->addBody('$this->setRecursionGuard($context);');
			if ($this->arguments->array) {
				$method->addBody('assert(is_array($data));');
			}

			$method->addBody('');
			$method->addBody('return $this->denormalizer->denormalize($data, $type, $format, $context);');

			// supportsDenormalization method
			$method = $class->addMethod('supportsDenormalization');
			$method->addParameter('data')->setType('mixed');
			$method->addParameter('type')->setType('string');
			$method->addParameter('format')->setType('?string')->setDefaultValue(null);
			$method->addParameter('context')->setType('array')->setDefaultValue([]);

			$method->addComment('@param mixed[] $context');

			$method->addBody(
				sprintf(
					'return !$this->isRecursion($context) && is_a($type, %s::class, true)%s;',
					$populateClassName ? $this->useStatements->use($populateClassName->getFullName(), true) : '',
					$this->arguments->array ? ' && is_array($data)' : '',
				));
		}


		if ($this->arguments->normalizer) {
			$class->addImplement($this->useStatements->use(ContextAwareNormalizerInterface::class));
			$class->addImplement($this->useStatements->use(NormalizerAwareInterface::class));
			$class->addTrait($this->useStatements->use(NormalizerAwareTrait::class));

			// normalize method
			$method = $class->addMethod('normalize');
			$method->addParameter('data')->setType('mixed');
			$method->addParameter('format')->setType('?string')->setDefaultValue(null);
			$method->addParameter('context')->setType('array')->setDefaultValue([]);

			$method->addComment('@param mixed[] $context');
			$method->addBody(
				sprintf(
					'assert($data instanceof %s);',
					$populateClassName ? $this->useStatements->use($populateClassName->getFullName(), true) : '',
				),
			);

			$method->addBody('$this->setRecursionGuard($context);');
			$method->addBody('');
			$method->addBody('return $this->normalizer->normalize($data, $format, $context);');

			// supportsNormalization method
			$method = $class->addMethod('supportsNormalization');
			$method->addParameter('data')->setType('mixed');
			$method->addParameter('format')->setType('?string')->setDefaultValue(null);
			$method->addParameter('context')->setType('array')->setDefaultValue([]);

			$method->addComment('@param mixed[] $context');

			$method->addBody(
				sprintf(
					'return !$this->isRecursion($context) && $data instanceof %s;',
					$populateClassName ? $this->useStatements->use($populateClassName->getFullName(), true) : '',
				));
		}
	}

}

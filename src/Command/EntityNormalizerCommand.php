<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command;


use Nette\PhpGenerator\ClassType;
use Nette\Utils\FileSystem;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use WebChemistry\Generette\Utility\FilePathUtility;
use WebChemistry\Generette\Utility\PhpClassNaming;
use WebChemistry\Serializer\Guard\SerializerRecursionGuard;
use WebChemistry\ServiceAttribute\Attribute\Service;

final class EntityNormalizerCommand extends GenerateCommand
{

	public static $defaultName = 'generate:normalizer:entity';

	public function __construct(
		private string $basePath,
		private string $namespace,
	)
	{
		parent::__construct();
	}

	protected function configure()
	{
		$this->setDescription('Generates entity normalizer / denormalizer')
			->addArgument('name', InputArgument::REQUIRED,'Normalizer name')
			->addOption('normalizer', 'o', InputOption::VALUE_NONE, 'Only normalizer')
			->addOption('denormalizer', 'd', InputOption::VALUE_NONE, 'Only denormalizer')
			->addOption('populate', 'p', InputOption::VALUE_REQUIRED, 'Populate default object')
			->addOption('array', 'a', InputOption::VALUE_NONE, 'Check if data is array')
			->addOption('constructor', 'c', InputOption::VALUE_NONE, 'Creates constructor');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		[$baseDir, $argumentName] = $this->extractBaseDirAndName($input->getArgument('name'));
		$normalizer = (bool) $input->getOption('normalizer');
		$denormalizer = (bool) $input->getOption('denormalizer');
		$constructor = (bool) $input->getOption('constructor');
		$populate = $input->getOption('populate');
		$array = $input->getOption('array');

		if (!$normalizer && !$denormalizer) {
			$normalizer = $denormalizer = true;
		}

		$className = PhpClassNaming::createWithMerge($this->namespace, $argumentName)->withAppendedClassName('Normalizer');

		// normalizer
		$file = $this->createPhpFile();
		$class = $this->createNamespaceFromFile($file, $className->getNamespace())->addClass($className->getClassName());
		$this->processClass($class, $normalizer, $denormalizer, $constructor, $array, $populate);

		FileSystem::createDir($baseDir = FilePathUtility::join($this->basePath, $baseDir));

		$filePath = FilePathUtility::join($baseDir, $className->getFileName());
		if (file_exists($filePath)) {
			$output->writeln($this->error(sprintf('File file://%s already exists.', $filePath)));

			return self::FAILURE;
		}
		FileSystem::write($filePath, $this->printer->printFile($file));

		$output->writeln('Created normalizer file://' . $filePath);

		return self::SUCCESS;
	}

	public function processClass(ClassType $class, bool $normalizer, bool $denormalizer, bool $constructor, bool $array, ?string $populate): void
	{
		$class->setFinal();
		$class->addTrait($this->useStatements->use(SerializerRecursionGuard::class));
		if ($constructor) {
			$class->addMethod('__construct');
		}

		if (class_exists(Service::class)) {
			$class->addAttribute($this->useStatements->use(Service::class));
		}

		if ($denormalizer) {
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
			if ($populate) {
				[,$className] = $this->extractBaseDirAndName($populate);
				$method->addBody(sprintf('/** @var %s|null $object */', $this->useStatements->use($className, true)));
				$method->addBody(
					sprintf(
						'$object = $context[%s::OBJECT_TO_POPULATE] ?? null;',
						$this->useStatements->use(AbstractNormalizer::class, true)
					)
				);
			}
			$method->addBody('$this->setRecursionGuard($context);');
			if ($array) {
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
					$populate ? $this->useStatements->use($this->extractBaseDirAndName($populate)[1], true) : '',
					$array ? ' && is_array($data)' : '',
				));
		}


		if ($normalizer) {
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
					$populate ? $this->useStatements->use($this->extractBaseDirAndName($populate)[1], true) : '',
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
					$populate ? $this->useStatements->use($this->extractBaseDirAndName($populate)[1], true) : '',
				));
		}
	}

}

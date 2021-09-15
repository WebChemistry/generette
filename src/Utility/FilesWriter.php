<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Utility;

use Nette\Utils\FileSystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class FilesWriter
{

	/** @var array<string, string> */
	private array $files;

	public function __construct(
		private InputInterface $input,
		private OutputInterface $output,
		private QuestionHelper $questionHelper,
	)
	{
	}

	public static function create(InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper): self
	{
		return new self($input, $output, $questionHelper);
	}

	public function addFile(string $filePath, string $content): self
	{
		$this->files[$filePath] = $content;

		return $this;
	}

	public function write(bool $overwriteForce = false): int
	{
		$files = $this->files;

		if (!$overwriteForce) {
			foreach ($files as $filePath => $_) {
				if ($this->filesExists($filePath) && !$this->question(sprintf('Overwrite %s file?', $this->markFile($filePath)))) {
					unset($files[$filePath]);
				}
			}
		}

		if (!$files) {
			return Command::FAILURE;
		}

		foreach ($files as $filePath => $content) {
			$directory = dirname($filePath);
			if ($directory !== '.') {
				FileSystem::createDir($directory);
			}

			FileSystem::write($filePath, $content);

			$this->output->writeln(sprintf('File file://%s created', $filePath));
		}

		return Command::SUCCESS;
	}

	private function markFile(string $filePath): string
	{
		return $this->output->getFormatter()->format(
			preg_replace('#/([^/]+)$#', '/<info>$1</info>', $filePath)
		);
	}

	private function question(string $question, bool $default = false): bool
	{
		$defaultTxt = $this->output->getFormatter()->format(sprintf('<comment>[%s]</comment>', $default ? 'yes' : 'no'));

		$ret = $this->questionHelper->ask(
			$this->input,
			$this->output,
			new ConfirmationQuestion(
				sprintf('%s %s ', $question, $defaultTxt),
				$default
			),
		);

		return $ret;
	}

	private function filesExists(string ...$files): bool
	{
		foreach ($files as $file) {
			if (!file_exists($file)) {
				return false;
			}
		}

		return true;
	}

}

<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Command;

use WebChemistry\Console\BaseCommand;
use WebChemistry\Generette\Composer\ComposerPathAutoload;
use WebChemistry\Generette\Utility\Factory\DefaultGeneretteFactory;
use WebChemistry\Generette\Utility\Factory\GeneretteFactory;
use WebChemistry\Generette\Utility\Generette;

abstract class GeneretteCommand extends BaseCommand
{

	protected Generette $generette;

	private ?ComposerPathAutoload $composer = null;

	private GeneretteFactory $generetteFactory;

	public function __construct(string $name = null)
	{
		$this->generetteFactory = new DefaultGeneretteFactory();

		parent::__construct($name);
	}

	public function setGeneretteFactory(GeneretteFactory $generetteFactory): static
	{
		$this->generetteFactory = $generetteFactory;

		return $this;
	}

	public function setComposerDir(?string $composerDir): static
	{
		$this->composer = $composerDir ? new ComposerPathAutoload(rtrim($composerDir, '/') . '/composer.json') : null;

		return $this;
	}

	protected function startup(): void
	{
		parent::startup();

		$this->generette = $this->generetteFactory->create($this->input, $this->output, $this, $this->composer);
	}

}

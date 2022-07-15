<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Utility\Factory;

use Nette\PhpGenerator\Printer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WebChemistry\Generette\Composer\ComposerPathAutoload;
use WebChemistry\Generette\Utility\Generette;

final class DefaultGeneretteFactory implements GeneretteFactory
{

	public function __construct(
		private ?Printer $printer = null,
	)
	{
	}

	public function create(
		InputInterface $input,
		OutputInterface $output,
		Command $command,
		?ComposerPathAutoload $composer,
	): Generette
	{
		return new Generette($input, $output, $command, $composer, $this->printer);
	}

}

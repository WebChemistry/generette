<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Utility\Factory;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WebChemistry\Generette\Composer\ComposerPathAutoload;
use WebChemistry\Generette\Utility\Generette;

interface GeneretteFactory
{

	public function create(
		InputInterface $input,
		OutputInterface $output,
		Command $command,
		?ComposerPathAutoload $composer,
	): Generette;

}

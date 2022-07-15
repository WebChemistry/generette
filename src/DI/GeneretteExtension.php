<?php declare(strict_types = 1);

namespace WebChemistry\Generette\DI;

use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use WebChemistry\Generette\Command\GeneretteCommand;

final class GeneretteExtension extends CompilerExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'composer' => Expect::string(),
		]);
	}

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();
		/** @var stdClass $config */
		$config = $this->getConfig();

		if (!$config->composer) {
			return;
		}

		foreach ($builder->findByType(GeneretteCommand::class) as $command) {
			if ($command instanceof ServiceDefinition) {
				$command->addSetup('setComposer', [$config->composer]);
			}
		}
	}

}

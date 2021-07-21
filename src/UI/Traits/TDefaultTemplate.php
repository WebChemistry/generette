<?php declare(strict_types = 1);

namespace WebChemistry\Generette\UI\Traits;

use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\Security\User;
use stdClass;

trait TDefaultTemplate
{

	public Presenter $presenter;

	public Control $control;

	public User $user;

	public string $baseUrl;

	public string $basePath;

	/** @var stdClass[] */
	public array $flashes = [];

}

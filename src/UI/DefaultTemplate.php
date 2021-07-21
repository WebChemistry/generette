<?php declare(strict_types = 1);

namespace WebChemistry\Generette\UI;

use WebChemistry\Generette\UI\Traits\TDefaultTemplate;
use Nette\Bridges\ApplicationLatte\Template;

abstract class DefaultTemplate extends Template
{

	use TDefaultTemplate;
}

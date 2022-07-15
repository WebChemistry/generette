<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Utility;

final class PhpClassNameWithNamespacePrefix extends PhpClassName
{

	private ?string $namespacePrefix = null;

	public function getNamespacePrefix(): ?string
	{
		return $this->namespacePrefix;
	}

	public function setNamespacePrefix(?string $namespacePrefix): static
	{
		$this->namespacePrefix = $namespacePrefix;

		return $this;
	}

	protected function createInstance(string $fullName): static
	{
		$instance = parent::createInstance($fullName);
		$instance->setNamespacePrefix($this->namespacePrefix);

		return $instance;
	}

}

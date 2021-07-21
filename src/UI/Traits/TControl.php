<?php declare(strict_types = 1);

namespace WebChemistry\Generette\UI\Traits;

trait TControl
{

	private string $templateClassName;

	/**
	 * @template T
	 * @param class-string<T> $className
	 * @return T
	 */
	public function getTemplateObject(string $className): object
	{
		$this->templateClassName = $className;

		return $this->getTemplate();
	}

	public function formatTemplateClass(): ?string
	{
		return $this->templateClassName;
	}

}

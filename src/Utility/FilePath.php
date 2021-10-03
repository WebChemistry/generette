<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Utility;

final class FilePath
{

	private string $path = '';

	public function __construct(string ... $paths)
	{
		foreach ($paths as $path) {
			if ($path = rtrim($path, '/')) {
				$this->path .= rtrim($path, '/') . '/';
			}
		}

		$this->path = $this->path ? substr($this->path, 0, -1) : $this->path;
	}

	public function withAppendedPath(string $path): self
	{
		return new FilePath($this->path, $path);
	}

	public function withPrependedPath(string $path): self
	{
		return new FilePath($path, $this->path);
	}

	public function toString(): string
	{
		return $this->path;
	}

}

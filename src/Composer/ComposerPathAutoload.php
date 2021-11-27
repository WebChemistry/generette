<?php declare(strict_types = 1);

namespace WebChemistry\Generette\Composer;

use LogicException;
use Nette\Utils\Arrays;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;

final class ComposerPathAutoload
{

	/** @var array<string, string[]> */
	private array $autoload;

	private string $basePath;

	private string $composerFile;

	public function __construct(
		string $composerFile,
	)
	{
		$this->composerFile = FileSystem::normalizePath($composerFile);
		$this->basePath = dirname($this->composerFile);
	}

	private function getAutoload(): array
	{
		if (!isset($this->autoload)) {
			$this->autoload = [];
			$json = Json::decode(FileSystem::read($this->composerFile), Json::FORCE_ARRAY);

			foreach ($json['autoload']['psr-4'] as $namespace => $path) {
				$namespace = rtrim($namespace, '\\');

				if (is_string($path)) {
					$this->autoload[$namespace] = $path;
				} else if (is_array($path)) {
					$this->autoload[$namespace] = Arrays::first($path);
				}
			}
		}

		return $this->autoload;
	}

	public function resolvePathByPsr4Namespace(?string $namespace): string
	{
		if (!$namespace) {
			foreach ($this->getAutoload() as $autoloadNamespace => $path) {
				if (!$autoloadNamespace) {
					return $this->basePath . '/' . trim($path, '/');
				}
			}


			throw new LogicException('Cannot resolve empty namespace to path.');
		}

		$namespace = rtrim($namespace, '\\');

		$bestPath = null;
		$bestNamespace = null;
		foreach ($this->getAutoload() as $autoloadNamespace => $path) {
			if (str_starts_with($namespace, $autoloadNamespace)) {
				if ($bestNamespace === null || strlen($autoloadNamespace) > $bestNamespace) {
					$bestPath = $path;
					$bestNamespace = $autoloadNamespace;
				}
			}
		}

		if ($bestPath === null || $bestNamespace === null) {
			throw new LogicException(sprintf('Cannot resolve namespace "%s" to path.', $namespace));
		}

		$namespacePath = strtr(
			ltrim(substr($namespace, strlen($bestNamespace)), '\\'),
			['\\' => '/']
		);

		return $this->basePath . '/' . trim($bestPath, '/') . ($namespacePath ? '/' . $namespacePath : '');
	}

}

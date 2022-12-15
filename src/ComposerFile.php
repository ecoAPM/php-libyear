<?php

namespace LibYear;

class ComposerFile
{
	const DEFAULT_URL = 'https://repo.packagist.org';

	private FileSystem $file_system;

	/** @var array[] */
	private array $cache = [];

	/** @var resource */
	private $stderr;

	public function __construct(FileSystem $file_system, $stderr)
	{
		$this->file_system = $file_system;
		$this->stderr = $stderr;
	}

	/**
	 * @param string $directory
	 * @return string[]
	 */
	public function getRepositories(string $directory): array
	{
		$json = $this->getComposerJSON($directory);

		$repositories = isset($json['repositories'])
			? array_filter($json['repositories'], fn($repository) => is_array($repository) && key_exists('url', $repository))
			: [];

		$urls = array_map(fn(array $repository) => rtrim($repository['url'], '/'), $repositories);

		if (!in_array(self::DEFAULT_URL, $urls) && (!isset($json['repositories']['packagist.org']) || $json['repositories']['packagist.org'] !== false)) {
			$urls[] = self::DEFAULT_URL;
		}

		return $urls;
	}

	/**
	 * @param string $directory
	 * @return Dependency[]
	 */
	public function getDependencies(string $directory): array
	{
		$packages = $this->getPackageNames($directory);
		$installed_versions = $this->getInstalledVersions($directory);

		$dependencies = [];
		foreach ($packages as $package_name => $declared_version) {
			$dependencies[$package_name] = self::createDependency($package_name, $declared_version, $installed_versions);
		}
		return $dependencies;
	}

	private function getPackageNames(string $directory): array
	{
		$json = $this->getComposerJSON($directory);

		return array_merge(
			array_key_exists('require', $json) ? $json['require'] : [],
			array_key_exists('require-dev', $json) ? $json['require-dev'] : []
		);
	}

	private function getInstalledVersions(string $directory): array
	{
		$json = $this->getComposerLock($directory);

		$installed_versions = [];
		$packages = array_merge(
			array_key_exists('packages', $json) ? $json['packages'] : [],
			array_key_exists('packages-dev', $json) ? $json['packages-dev'] : []
		);

		foreach ($packages as $package_info) {
			$installed_versions[$package_info['name']] = $package_info['version'];
		}

		return $installed_versions;
	}

	private static function createDependency(
		string $package_name,
		string $declared_version,
		array $installed_versions
	): Dependency
	{
		$dependency = new Dependency();
		$dependency->name = $package_name;
		$dependency->current_version->version_number = array_key_exists($package_name, $installed_versions)
			? $installed_versions[$package_name]
			: $declared_version;

		return $dependency;
	}

	private function getComposerJSON(string $directory): array
	{
		$path = self::jsonPath($directory);
		return $this->getComposerFile($path);
	}

	private function getComposerLock(string $directory): array
	{
		$path = self::lockPath($directory);
		return $this->getComposerFile($path);
	}

	private function getComposerFile(string $path): array
	{
		if (array_key_exists($path, $this->cache)) {
			return $this->cache[$path];
		}

		if (!$this->file_system->exists($path)) {
			fwrite($this->stderr, "File not found at $path\n");
			return [];
		}

		$this->cache[$path] = $this->file_system->getJSON($path);
		return $this->cache[$path];
	}

	private static function jsonPath(string $directory): string
	{
		return $directory . DIRECTORY_SEPARATOR . 'composer.json';
	}

	private static function lockPath(string $directory): string
	{
		return $directory . DIRECTORY_SEPARATOR . 'composer.lock';
	}
}
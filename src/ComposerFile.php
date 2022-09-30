<?php

namespace LibYear;

class ComposerFile
{
	const DEFAULT_URL = 'https://repo.packagist.org';

	private FileSystem $file_system;

	/** @var array[] */
	private array $json_cache;

	/** @var array[] */
	private array $lock_cache;

	public function __construct(FileSystem $file_system)
	{
		$this->file_system = $file_system;
	}

	/**
	 * @param string $directory
	 * @return string[]
	 */
	public function getRepositories(string $directory): array
	{
		$this->json_cache[$directory] ??= $this->file_system->getJSON($directory . DIRECTORY_SEPARATOR . 'composer.json');
		$json = $this->json_cache[$directory];

		$repositories = isset($json['repositories'])
			? array_filter($json['repositories'], fn($repository) => is_array($repository) && key_exists('url', $repository))
			: [];

		$urls = array_map(fn(array $repository) => rtrim($repository['url'], '/'), $repositories);

		if (!in_array(self::DEFAULT_URL, $urls)
			&& (!isset($json['repositories']['packagist.org'])
				|| $json['repositories']['packagist.org'] !== false)) {
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
		$this->json_cache[$directory] ??= $this->file_system->getJSON($directory . DIRECTORY_SEPARATOR . 'composer.json');
		$json = $this->json_cache[$directory];

		return array_merge(
			array_key_exists('require', $json) ? $json['require'] : [],
			array_key_exists('require-dev', $json) ? $json['require-dev'] : []
		);
	}

	private function getInstalledVersions(string $directory): array
	{
		$this->lock_cache[$directory] ??= $this->file_system->getJSON($directory . DIRECTORY_SEPARATOR . 'composer.lock');
		$json = $this->lock_cache[$directory];

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
		array  $installed_versions
	): Dependency
	{
		$dependency = new Dependency();
		$dependency->name = $package_name;
		$dependency->current_version->version_number = array_key_exists($package_name, $installed_versions)
			? $installed_versions[$package_name]
			: $declared_version;

		return $dependency;
	}
}

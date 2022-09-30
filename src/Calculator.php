<?php

namespace LibYear;

use Composer\Semver\Semver;
use DateTime;
use DateTimeInterface;

class Calculator
{
	private ComposerFile $composer;
	private RepositoryAPI $repo_api;

	public function __construct(ComposerFile $composer, RepositoryAPI $repo_api)
	{
		$this->composer = $composer;
		$this->repo_api = $repo_api;
	}

	/**
	 * @param string $directory
	 * @return Dependency[]
	 */
	public function getDependencyInfo(string $directory): array
	{
		$repository_urls = $this->composer->getRepositories($directory);
		$repositories = array_map(fn(string $url) => $this->repo_api->getInfo($url), $repository_urls);

		$dependencies = $this->composer->getDependencies($directory);

		foreach ($dependencies as $dependency) {
			$this->updateDependency($dependency, $repositories);
		}

		return $dependencies;
	}

	/**
	 * @param Dependency $dependency
	 * @param Repository[] $repositories
	 * @return void
	 */
	private function updateDependency(Dependency $dependency, array $repositories)
	{
		$package_info = [];
		foreach ($repositories as $repository) {
			$package_info = $this->repo_api->getPackageInfo($dependency->name, $repository);
			if (!empty($package_info)) {
				break;
			}
		}

		if (empty($package_info)) {
			return;
		}

		$versions = self::getVersions($package_info);
		if (empty($versions)) {
			return;
		}

		$sorted_versions = Semver::rsort(array_keys($versions));
		$dependency->current_version->released = $this->getReleaseDate($sorted_versions, $versions, $dependency->current_version->version_number);
		$newest_version = $sorted_versions[0];
		$dependency->newest_version->version_number = $newest_version;
		$dependency->newest_version->released = $versions[$newest_version];
	}

	/**
	 * @param array $releases
	 * @return DateTimeInterface[]
	 */
	private static function getVersions(array $releases): array
	{
		$versions = [];
		foreach ($releases as $release) {
			$versions[$release['version']] = self::findReleaseDate($release);
		}
		return $versions;
	}

	private static function findReleaseDate(array $release): ?DateTime
	{
		if (isset($release['time'])) {
			return new DateTime($release['time']);
		} elseif (isset($release['extra']['drupal']['datestamp'])) {
			return (new DateTime())->setTimestamp($release['extra']['drupal']['datestamp']);
		} else {
			return null;
		}
	}

	private static function getReleaseDate(array $sorted_versions, array $versions, string $current_version): ?DateTimeInterface
	{
		foreach ($sorted_versions as $version_to_check) {
			if (Semver::satisfies($version_to_check, $current_version)) {
				return $versions[$version_to_check];
			}
		}

		return null;
	}

	/**
	 * @param Dependency[] $dependencies
	 * @return float
	 */
	public static function getTotalLibyearsBehind(array $dependencies): float
	{
		return array_sum(array_map(fn(Dependency $dependency): float => $dependency->getLibyearsBehind() ?? 0, $dependencies));
	}
}

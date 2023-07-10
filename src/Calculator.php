<?php

namespace ecoAPM\LibYear;

use cli\Progress;
use Composer\Semver\Semver;
use DateTimeImmutable;
use DateTimeInterface;

class Calculator
{
	private ComposerFile $composer;
	private RepositoryAPI $repo_api;
	private Progress $progress;

	public function __construct(ComposerFile $composer, RepositoryAPI $repo_api, Progress $bar)
	{
		$this->composer = $composer;
		$this->repo_api = $repo_api;
		$this->progress = $bar;
	}

	/**
	 * @param string $directory
	 * @param bool $verbose
	 * @return Dependency[]
	 */
	public function getDependencyInfo(string $directory, bool $verbose): array
	{
		$repository_urls = $this->composer->getRepositories($directory);
		$repositories = array_map(fn(string $url) => $this->repo_api->getInfo($url, $verbose), $repository_urls);

		$dependencies = $this->composer->getDependencies($directory);

		$this->progress->setTotal(count($dependencies));
		$this->progress->display();
		foreach ($dependencies as $dependency) {
			$this->updateVersionInfo($dependency, array_filter($repositories), $verbose);
			$this->progress->tick();
		}
		$this->progress->finish();

		return $dependencies;
	}

	/**
	 * @param Dependency $dependency
	 * @param Repository[] $repositories
	 * @param bool $verbose
	 * @return void
	 */
	private function updateVersionInfo(Dependency $dependency, array $repositories, bool $verbose)
	{
		$package_info = [];
		foreach ($repositories as $repository) {
			$package_info = $this->repo_api->getPackageInfo($dependency->name, $repository, $verbose);
			if (!empty($package_info)) {
				break;
			}
		}

		$versions = self::getVersions($package_info);
		if (empty($versions)) {
			return;
		}

		$sorted_versions = Semver::rsort(array_keys($versions));

		$current_version = $dependency->current_version->version_number;
		$current_version_release_date = $this->getReleaseDate($sorted_versions, $versions, $current_version);
		$dependency->current_version->released = $current_version_release_date;

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
		return array_filter($versions);
	}

	private static function findReleaseDate(array $release): ?DateTimeImmutable
	{
		if (isset($release['time'])) {
			return new DateTimeImmutable($release['time']);
		}

		return isset($release['extra']['drupal']['datestamp'])
			? (new DateTimeImmutable())->setTimestamp($release['extra']['drupal']['datestamp'])
			: null;
	}

	private static function getReleaseDate(
		array  $sorted_versions,
		array  $versions,
		string $current_version
	): ?DateTimeInterface
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
		return array_sum(
			array_map(fn(Dependency $dependency): float => $dependency->getLibyearsBehind() ?? 0, $dependencies)
		);
	}
}

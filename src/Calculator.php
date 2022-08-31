<?php

namespace LibYear;

use Composer\Semver\Semver;
use DateTime;

class Calculator
{
    private ComposerFile $composer;
    private PackageAPI $packageAPI;

    public function __construct(ComposerFile $composer, PackageAPI $packageAPI)
    {
        $this->composer = $composer;
        $this->packageAPI = $packageAPI;
    }

    /**
     * @param string $directory
     * @return Dependency[]
     */
	public function getDependencyInfo(string $directory): array
	{
		$dependencies = $this->composer->getDependencies($directory);
		$repositories = array_map(
			fn($repositoryUrl) => $this->packageAPI->getRepositoryInfo($repositoryUrl),
			$this->composer->getRepositoriesUrl($directory) ?: ['https://repo.packagist.org']
		);

		foreach ($dependencies as $dependency) {
			foreach ($repositories as $repository) {
				if (!$repository->hasPackage($dependency->name)) {
					continue;
				}
				$package_info = $this->packageAPI->getPackageInfo($dependency->name, $repository->getPackageUrl($dependency->name));
				if (!empty($package_info)) {
					break;
				}
			}
			if (empty($package_info)) {
				continue;
			}

			$sorted_versions = self::sortVersions($package_info);
			if (empty($sorted_versions)) {
				continue;
			}

			$dependency->current_version->released = $this->findReleaseDate($sorted_versions, $package_info, $dependency->current_version->version_number);
			$dependency->newest_version->version_number = $sorted_versions[0];
			$dependency->newest_version->released = self::getReleaseDate($package_info, $sorted_versions[0]);
		}

		return $dependencies;
	}

    private static function sortVersions(array $package_info): array
    {
        return Semver::rsort(array_filter(array_keys($package_info), fn (string $version): bool => strpos($version, '-') === false));
    }

    private static function findReleaseDate(array $sorted_versions, array $package_info, string $current_version): ?DateTime
    {
        foreach ($sorted_versions as $version_to_check) {
            if (Semver::satisfies($version_to_check, $current_version)) {
                return self::getReleaseDate($package_info, $version_to_check);
            }
        }

        return null;
    }

    private static function getReleaseDate(array $package_info, string $version): DateTime
    {
        return new DateTime($package_info[$version]['time']);
    }

    /**
     * @param Dependency[] $dependencies
     * @return float
     */
    public static function getTotalLibyearsBehind(array $dependencies): float
    {
        return array_sum(array_map(fn (Dependency $dependency): float => $dependency->getLibyearsBehind() ?? 0, $dependencies));
    }
}

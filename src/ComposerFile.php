<?php

namespace LibYear;

class ComposerFile
{
    private FileSystem $file_system;

    public function __construct(FileSystem $file_system)
    {
        $this->file_system = $file_system;
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
        $composer_json = $this->file_system->getJSON($directory . DIRECTORY_SEPARATOR . 'composer.json');

        $packages = array_merge(
            array_key_exists('require', $composer_json) ? $composer_json['require'] : [],
            array_key_exists('require-dev', $composer_json) ? $composer_json['require-dev'] : []
        );
        return $packages;
    }

    private function getInstalledVersions(string $directory): array
    {
        $lock_json = $this->file_system->getJSON($directory . DIRECTORY_SEPARATOR . 'composer.lock');
        $installed_versions = [];
        $packages = array_merge(
            array_key_exists('packages', $lock_json) ? $lock_json['packages'] : [],
            array_key_exists('packages-dev', $lock_json) ? $lock_json['packages-dev'] : []
        );
        foreach ($packages as $package_info)
            $installed_versions[$package_info['name']] = $package_info['version'];
        return $installed_versions;
    }

    private static function createDependency(string $package_name, string $declared_version, array $installed_versions): Dependency
    {
        $dependency = new Dependency();
        $dependency->name = $package_name;
        $dependency->current_version->version_number = array_key_exists($package_name, $installed_versions) ? $installed_versions[$package_name] : $declared_version;
        return $dependency;
    }
}

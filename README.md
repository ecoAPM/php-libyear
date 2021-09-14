# php-libyear
A simple measure of dependency freshness

[![Version](https://img.shields.io/packagist/v/ecoapm/libyear?logo=packagist&label=Install)](https://packagist.org/packages/ecoAPM/libyear)
[![CI](https://github.com/ecoAPM/php-libyear/workflows/CI/badge.svg)](https://github.com/ecoAPM/php-libyear/actions)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=ecoAPM_php-libyear&metric=coverage)](https://sonarcloud.io/dashboard?id=ecoAPM_php-libyear)

[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=ecoAPM_php-libyear&metric=sqale_rating)](https://sonarcloud.io/dashboard?id=ecoAPM_php-libyear)
[![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=ecoAPM_php-libyear&metric=reliability_rating)](https://sonarcloud.io/dashboard?id=ecoAPM_php-libyear)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=ecoAPM_php-libyear&metric=security_rating)](https://sonarcloud.io/dashboard?id=ecoAPM_php-libyear)


Calculates the total number of years behind their respective newest versions for all dependencies listed in `composer.json`.

## Requirements

- PHP 7.4 or later 

## Installation

Simply download `libyear.phar` from the latest release.

Put it in the current directory, or your `$PATH` to be able to reference it from anywhere.

## Usage
`php libyear.phar { path to project } [-q]`

Arguments:
- `path to project`: required, directory containing `composer.json` and `composer.lock` files
- `-q`: optional, quiet mode will only output libraries which are not up-to-date (that is, where "Libyears Behind" > 0)

## Contributing

When testing new features and bug fixes, you can run the script via `php libyear.php { path } [-q]` before building the `phar` file.

To build the `phar` file for final manual testing, run `php -dphar.readonly=0 build.php`. You may wish to run `composer install --no-dev` first to speed up the build process by removing dev dependencies, but will need to reinstall them via `composer install` (specifically, `phpunit` and `mockery` for unit testing).

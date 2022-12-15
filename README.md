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

- PHP v7.4 or later
- Composer v2

## Installation

### Recommended: Composer

Run `composer global require ecoapm/libyear` and make sure your global composer directory is in your `$PATH`.

Alternatively, `composer require --dev ecoapm/libyear` will add `libyear` as a local dev dependency for your current directory's app.

### Alternative: PHP Archive (PHAR) File

Download `libyear.phar` from the latest release, and put it in the current directory, or somewhere in your `$PATH` to be able to reference it from anywhere.

### Windows Users

Note that PHP for Windows does not include CA certificates, so you'll need to install them if you haven't done so already:
1. Download http://curl.haxx.se/ca/cacert.pem to somewhere permanent (PHP's `extras` directory is a great place)
1. Add `curl.cainfo = "[full path to]\cacert.pem"` to your `php.ini` file

## Usage

`vendor/bin/libyear <path> [-q|--quiet] [-v|--verbose]`

(or `php path/to/libyear.phar <path> [-q|--quiet] [-v|--verbose]` for the PHAR version)

Arguments:
- `path`: required, directory containing `composer.json` and `composer.lock` files

Options:
- `-h`, `--help`: show help text and exit without checking dependencies
- `-q`, `--quiet`: quiet mode will only output libraries which are not up-to-date (that is, where "Libyears Behind" > 0)
- `-u`, `--update`: update mode will write the latest version info to your `composer.json` file (note that you'll still need to run `composer update` to actually update your local dependencies)
- `-v`, `--verbose`: verbose mode will output processing details like when a library isn't found in a repository

## Contributing

Please be sure to read and follow ecoAPM's [Contribution Guidelines](CONTRIBUTING.md) when submitting issues or pull requests.

When testing new features and bug fixes, you can run the script via `./libyear { path } [-q]` before building the `phar` file.

To build the `phar` file for final manual testing, run `php -dphar.readonly=0 build.php`. You may wish to run `composer install --no-dev` first to speed up the build process by removing dev dependencies, but will need to reinstall them via `composer install` (specifically, `phpunit` and `mockery` for unit testing).

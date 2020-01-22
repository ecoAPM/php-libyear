# php-libyear
A simple measure of dependency freshness

Calculates the total number of years behind their respective newest versions for all dependencies listed in `composer.json`.

## Usage
`php libyear.phar { path to project } [-q]`

Arguments:
- `path to project`: required, directory containing `composer.json` and `composer.lock` files
- `-q`: optional, quiet mode will only output libraries which are not up-to-date (that is, where "Libyears Behind" > 0)

## Contributing

When testing new features and bug fixes, you can run the script via `php libyear.php { path } [-q]` before building the `phar` file.

To build the `phar` file for final manual testing, run `php -dphar.readonly=0 build.php`. You may wish to run `composer install --no-dev` first to speed up the build process by removing dev dependencies, but will need to reinstall them via `composer install` (specifically, `phpunit` and `mockery` for unit testing).

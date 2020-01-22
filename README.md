# php-libyear
A simple measure of dependency freshness

## Usage
`php libyear.phar { path to project } [-q]`

Arguments:
- `path to project`: required, directory containing `composer.json` and `composer.lock` files
- `-q`: optional, quiet mode will only output libraries that are not up-to-date
<?php

namespace ecoAPM\LibYear;

use cli\Table;

class App
{
	private Calculator $calculator;
	private ComposerFile $composer;

	/** @var resource */
	private $output;

	/**
	 * @param Calculator $calculator
	 * @param ComposerFile $composer
	 * @param resource $output
	 */
	public function __construct(Calculator $calculator, ComposerFile $composer, $output)
	{
		$this->calculator = $calculator;
		$this->composer = $composer;
		$this->output = $output;
	}

	/**
	 * @param string[] $args
	 */
	public function run(array $args): void
	{
		if (in_array('-h', $args) || in_array('--help', $args))
		{
			$this->showHelp();
			return;
		}

		$quiet_mode = in_array('-q', $args) || in_array('--quiet', $args);
		$update_mode = in_array('-u', $args) || in_array('--update', $args);
		$verbose_mode = in_array('-v', $args) || in_array('--verbose', $args);
		$known_options = ['-q', '--quiet', '-u', '--update', '-v', '--verbose'];
		$other_args = array_filter(array_slice($args, 1), fn ($a) => !in_array($a, $known_options));
		$dir = !empty($other_args) ? array_values($other_args)[0] : '.';

		$real_dir = realpath($dir);
		fwrite($this->output, "Gathering information for $real_dir...\n");

		$dependencies = $this->getDependencies($dir, $quiet_mode, $verbose_mode);
		if (!empty($dependencies)) {
			$this->showTable($dependencies);
		}

		$total = Calculator::getTotalLibyearsBehind($dependencies);
		$total_display = number_format($total, 2);

		fwrite($this->output, "Total: $total_display libyears behind\n");

		if ($update_mode) {
			$this->composer->update($dir, $dependencies);
			fwrite($this->output, "composer.json updated\n");
		}
	}

	/**
	 * @param string $dir
	 * @param bool $quiet_mode
	 * @param bool $verbose_mode
	 * @return Dependency[]
	 */
	private function getDependencies(string $dir, bool $quiet_mode, bool $verbose_mode): array
	{
		$dependencies = $this->calculator->getDependencyInfo($dir, $verbose_mode);
		return $quiet_mode
			? array_filter($dependencies, fn (Dependency $dependency): bool => $dependency->getLibyearsBehind() > 0)
			: $dependencies;
	}

	private function showTable(array $dependencies): void
	{
		$table = new Table(
			['Package', 'Current Version', 'Released', 'Newest Version', 'Released', 'Libyears Behind'],
			array_map(
				fn (Dependency $dependency): array => [
					$dependency->name,
					$dependency->current_version->version_number,
					isset($dependency->current_version->released) ? $dependency->current_version->released->format('Y-m-d') : "",
					isset($dependency->newest_version->version_number) ? $dependency->newest_version->version_number : "",
					isset($dependency->newest_version->released) ? $dependency->newest_version->released->format('Y-m-d') : "",
					$dependency->getLibyearsBehind() !== null ? number_format($dependency->getLibyearsBehind(), 2) : ""
				],
				$dependencies
			)
		);

		$rows = $table->getDisplayLines();
		foreach ($rows as $row) {
			fwrite($this->output, $row . "\n");
		}
	}

	private function showHelp(): void
	{
		$output = 'libyear: a simple measure of dependency freshness' . PHP_EOL
			. PHP_EOL
			. 'Calculates the total number of years behind their respective newest versions for all'
				. ' dependencies listed in composer.json.' . PHP_EOL
			. PHP_EOL
			. 'Usage: libyear <path> [-q|--quiet] [-u|--update] [-v|--verbose]' . PHP_EOL
			. PHP_EOL
			. 'Arguments:' . PHP_EOL
			. '- path (required) the directory containing composer.json and composer.lock files' . PHP_EOL
			. PHP_EOL
			. 'Options:' . PHP_EOL
			. '--help    (-h)    show this message and exit' . PHP_EOL
			. '--quiet   (-q)    only display outdated dependencies' . PHP_EOL
			. '--update  (-u)    update composer.json with newest versions' . PHP_EOL
			. '--verbose (-v)    display network debug information' . PHP_EOL
			. PHP_EOL;

			fwrite($this->output, $output);
	}
}

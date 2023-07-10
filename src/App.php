<?php

namespace ecoAPM\LibYear;

use cli\Table;
use Garden\Cli\Cli;
use Exception;

class App
{
	private Calculator $calculator;
	private ComposerFile $composer;

	/** @var resource */
	private $output;
	private Cli $cli;

	/**
	 * @param Cli $cli
	 * @param Calculator $calculator
	 * @param ComposerFile $composer
	 * @param resource $output
	 */
	public function __construct(Cli $cli, Calculator $calculator, ComposerFile $composer, $output)
	{
		$this->cli = $cli->description('libyear: a simple measure of dependency freshness -- calculates the total number of years behind their respective newest versions for all dependencies listed in a composer.json file.')
			->opt('quiet:q', 'only display outdated dependencies')
			->opt('update:u', 'update composer.json with newest versions')
			->opt('verbose:v', 'display network debug information')
			->opt('limit:l', 'fails if total libyears behind is greater than this value')
			->opt('limit-any:a', 'fails if any dependency is more libyears behind than this value')
			->arg('path', 'the directory containing composer.json and composer.lock files');
		$this->calculator = $calculator;
		$this->composer = $composer;
		$this->output = $output;
	}

	/**
	 * @param string[] $args
	 */
	public function run(array $args): bool
	{
		try {
			$arguments = $this->cli->parse($args, false);
		} catch (Exception $e) {
			$msg = $e->getMessage();
			fwrite($this->output, "{$msg}\n");
			if (!str_starts_with($msg, "usage: ")) {
				$this->showHelp();
				return false;
			}
			return true;
		}

		$quiet_mode = $arguments->getOpt('quiet') !== null;
		$update_mode = $arguments->getOpt('update') !== null;
		$verbose_mode = $arguments->getOpt('verbose') !== null;
		$limit_total = $arguments->getOpt('limit');
		$limit_any = $arguments->getOpt('limit-any');
		$dir = $arguments->getArg('path') ?? '.';

		$real_dir = realpath($dir);
		fwrite($this->output, "Gathering information for $real_dir...\n");

		$dependencies = $this->getDependencies($dir, $quiet_mode, $verbose_mode);
		if (!empty($dependencies)) {
			$this->showTable($dependencies);
		}

		$total = Calculator::getTotalLibyearsBehind($dependencies);
		$total_display = number_format($total, 2);

		fwrite($this->output, "Total: $total_display libyears behind\n");

		if ($limit_any != null) {
			$beyond_limit = array_filter($dependencies, fn($d) => $d->getLibyearsBehind() > $limit_any);

			/** @var Dependency $dependency */
			foreach ($beyond_limit as $dependency) {
				$behind = number_format($dependency->getLibyearsBehind(), 2);
				fwrite($this->output, "{$dependency->name} is {$behind} libyears behind, which is greater than the set limit of {$limit_any}\n");
			}

			return sizeof($beyond_limit) == 0;
		}

		if ($limit_total != null && $total > $limit_total) {
			fwrite($this->output, "Total libyears behind is greater than the set limit of {$limit_total}\n");
			return false;
		}

		if ($update_mode) {
			$this->composer->update($dir, $dependencies);
			fwrite($this->output, "composer.json updated\n");
			fwrite($this->output, "A manual run of \"composer update\" is required to actually update dependencies\n");
		}

		return true;
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
					isset($dependency->current_version->released) ? $dependency->current_version->released->format('Y-m-d') : '',
					isset($dependency->newest_version->version_number) ? $dependency->newest_version->version_number : '',
					isset($dependency->newest_version->released) ? $dependency->newest_version->released->format('Y-m-d') : '',
					$dependency->getLibyearsBehind() !== null ? number_format($dependency->getLibyearsBehind(), 2) : ''
				],
				$dependencies
			)
		);

		$rows = $table->getDisplayLines();
		foreach ($rows as $row) {
			fwrite($this->output, $row . "\n");
		}
	}

	/**
	 * @return void
	 */
	public function showHelp(): void
	{
		ob_start();
		$this->cli->writeHelp();
		$output = ob_get_clean();
		fwrite($this->output, $output);
	}
}

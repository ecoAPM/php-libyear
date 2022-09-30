<?php

namespace LibYear;

use cli\Table;

class App
{
	private Calculator $calculator;

	/** @var resource */
	private $output;

	/**
	 * @param Calculator $calculator
	 * @param resource $output
	 */
	public function __construct(Calculator $calculator, $output)
	{
		$this->calculator = $calculator;
		$this->output = $output;
	}

	/**
	 * @param string[] $args
	 */
	public function run(array $args): void
	{
		$quiet_mode = in_array('-q', $args) || in_array('--quiet', $args);
		$dir = $args[1] ?? '.';

		$real_dir = realpath($dir);
		fwrite($this->output, "Gathering information for $real_dir...\n");

		$dependencies = $this->getDependencies($dir, $quiet_mode);

		$table = new Table(
			['Package', 'Current Version', 'Released', 'Newest Version', 'Released', 'Libyears Behind'],
			array_map(
				fn(Dependency $dependency): array => [
					$dependency->name,
					$dependency->current_version->version_number,
					isset($dependency->current_version->released) ? $dependency->current_version->released->format('Y-m-d') : "",
					isset($dependency->newest_version->version_number) ? $dependency->newest_version->version_number : "",
					isset($dependency->newest_version->released) ? $dependency->newest_version->released->format('Y-m-d') : "",
					$dependency->getLibyearsBehind() !== null ? number_format($dependency->getLibyearsBehind(), 2) : ""
				]
				, $dependencies
			)
		);

		$rows = $table->getDisplayLines();
		foreach ($rows as $row) {
			fwrite($this->output, $row . "\n");
		}

		$total = Calculator::getTotalLibyearsBehind($dependencies);
		$total_display = number_format($total, 2);

		fwrite($this->output, "Total: $total_display libyears behind\n");
	}

	/**
	 * @param string $dir
	 * @param bool $quiet_mode
	 * @return Dependency[]
	 */
	private function getDependencies(string $dir, bool $quiet_mode): array
	{
		$dependencies = $this->calculator->getDependencyInfo($dir);
		return $quiet_mode
			? array_filter($dependencies, fn(Dependency $dependency): bool => $dependency->getLibyearsBehind() > 0)
			: $dependencies;
	}
}

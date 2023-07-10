<?php

namespace ecoAPM\LibYear;

use DateTimeInterface;

class Version
{
	public string $version_number;
	public ?DateTimeInterface $released = null;

	public function __construct(string $version_number = null)
	{
		if ($version_number != null) {
			$this->version_number = $version_number;
		}
	}
}

<?php

namespace LibYear;

use DateTimeInterface;

class Version
{
	public string $version_number;
	public ?DateTimeInterface $released = null;
}

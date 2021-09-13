<?php

namespace LibYear;

class Dependency
{
    public string $name;
    public Version $current_version;
    public Version $newest_version;

    public function __construct()
    {
        $this->current_version = new Version();
        $this->newest_version = new Version();
    }

    public function getLibyearsBehind(): ?float
    {
        if (!isset($this->newest_version->released) || !isset($this->current_version->released))
            return null;

        $age = $this->newest_version->released->diff($this->current_version->released);
        return $age->days / (365 + 97 / 400);
    }
}

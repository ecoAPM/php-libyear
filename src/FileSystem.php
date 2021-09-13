<?php

namespace LibYear;

class FileSystem
{
    public function getJSON(string $filename): array
    {
        $file_contents = file_get_contents($filename);
        return json_decode($file_contents, true);
    }
}
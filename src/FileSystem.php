<?php

namespace ecoAPM\LibYear;

class FileSystem
{
	public function exists(string $filename): bool
	{
		return file_exists($filename);
	}

	public function getJSON(string $filename): array
	{
		$file_contents = file_get_contents($filename);
		return json_decode($file_contents, true);
	}

	public function saveJSON(string $filename, array $data): void
	{
		$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		file_put_contents($filename, $json);
	}
}

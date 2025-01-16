<?php

namespace App\Map;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;

class TilesRepository
{
    public function __construct(
        #[Autowire(env: 'resolve:DATA_DIRECTORY')]
        private readonly string $dataDirectory,
    ) {}

    public function has(string $schema, string $name): bool
    {
        $files = (new Finder())->files()->in($this->dataDirectory . '/tiles/pmtiles/' . $schema)->depth('== 0')->name($name . '.pmtiles');

        return iterator_count($files) === 1;
    }

    public function list(): array
    {
        $results = [];
        $files = (new Finder())->files()->in($this->dataDirectory . '/tiles/pmtiles')->name('*.pmtiles');

        foreach ($files as $file) {
            $schema = basename($file->getPath());

            if (!isset($results[$schema])) {
                $results[$schema] = [];
            }

            $results[$schema][] = $file;
        }

        return $results;
    }
}

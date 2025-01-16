<?php

namespace maps\utilities;

use ZipArchive;

use function Castor\http_download;
use function Castor\io;
use function Castor\fs;
use function Castor\run;
use function Castor\variable;

function download(string $source, string $targetFilename): void
{
    $progressBar = io()->createProgressBar();

    $divider = 1;
    http_download($source, $targetFilename, options: [
        'headers' => [
            'User-Agent' => 'maps.jolicode.com',
        ],
        'on_progress' => function (int $dlNow, int $dlSize) use ($progressBar, $divider): void {
            if ($dlSize !== 0) {
                while ($dlSize / $divider > 1024 * 1024) {
                    $divider *= 1024;
                }
                $progressBar->setMaxSteps(ceil($dlSize / $divider));
                $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:16s%/%estimated:-16s%');
            }

            $progressBar->setProgress(floor($dlNow / $divider));
        },
    ], stream: true);
    $progressBar->finish();
    io()->writeln('');
}

function create_directories(): void
{
    $directories = [
        'data/bin',
        'data/bin/pmtiles',
        'data/tiles',
        'data/tiles/mbtiles',
        'data/tiles/pbf',
        'data/tiles/pmtiles',
        'data/resources',
        'data/resources/shapefiles',
        'data/resources/styles/versatiles-style',
        'data/tmp/store',
    ];

    foreach ($directories as $directory) {
        if (!fs()->exists(variable('maps_data_folder') . DIRECTORY_SEPARATOR . $directory)) {
            fs()->mkdir(variable('maps_data_folder') . DIRECTORY_SEPARATOR . $directory);
        }
    }
}

function get_tile_filepath(string $schema, string $name, string $type, bool $relative = false): string
{
    $path = match ($type) {
        'mbtiles' => sprintf('data/tiles/mbtiles/%s/%s.mbtiles', $schema, $name),
        'pmtiles' => sprintf('data/tiles/pmtiles/%s/%s.pmtiles', $schema, $name),
        default => throw new \InvalidArgumentException(sprintf('Unknown type %s', $type)),
    };

    if (!$relative) {
        return sprintf('%s/%s', variable('maps_data_folder'), $path);
    }

    return $path;
}

/**
 * @return string[]
 */
function get_mbtiles_filenames(): array
{
    $tileFiles = get_tile_files_list('mbtiles');
    $mbtiles = [];

    foreach ($tileFiles as $file) {
        $mbtiles[] = basename(dirname($file)) . '/' . basename($file, '.mbtiles');
    }

    return $mbtiles;
}

/**
 * @return string[]
 */
function get_pbf_filenames(): array
{
    $tileFiles = get_tile_files_list('pbf');
    $mbtiles = [];

    foreach ($tileFiles as $file) {
        $mbtiles[] = basename($file, '.osm.pbf');
    }

    return $mbtiles;
}

function get_tile_files_list(string $type): array
{
    $tilesFolder = sprintf('%s/data/tiles/%s', variable('maps_data_folder'), $type);

    if (!fs()->exists($tilesFolder)) {
        return [];
    }

    return glob(sprintf('%s/{,*/}*.%s', $tilesFolder, $type), \GLOB_BRACE);
}

function unarchive(string $filename, string $target): void
{
    if (str_ends_with($filename, '.zip')) {
        $zip = new ZipArchive();
        $resource = $zip->open($filename);

        if ($resource === true) {
            $zip->extractTo($target);
            $zip->close();
        } else {
            io()->error(sprintf('Failed to extract %s', $filename));
        }

        return;
    } else if (str_ends_with($filename, '.tar.gz')) {
        $command = [
            'tar',
            '-xzf',
            $filename,
            '-C',
            $target,
        ];
        run($command);

        return;
    } else {
        io()->error(sprintf('Unknown archive type %s', $filename));
    }
}

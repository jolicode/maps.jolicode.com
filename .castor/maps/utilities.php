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
        'bin',
        'bin/pmtiles',
        'tiles',
        'tiles/mbtiles',
        'tiles/pbf',
        'tiles/pmtiles',
        'resources',
        'resources/shapefiles',
        'resources/styles/versatiles-style',
        'tmp/store',
    ];

    foreach ($directories as $directory) {
        if (!fs()->exists(variable('maps_data_folder') . DIRECTORY_SEPARATOR . $directory)) {
            fs()->mkdir(variable('maps_data_folder') . DIRECTORY_SEPARATOR . $directory);
        }
    }
}

function get_filepath(string $name, string $type): string
{
    switch ($type) {
        case 'pbf':
            return sprintf('%s/tiles/pbf/%s.osm.pbf', variable('maps_data_folder'), $name);
        case 'mbtiles':
            return sprintf('%s/tiles/mbtiles/%s.mbtiles', variable('maps_data_folder'), $name);
        case 'pmtiles':
            return sprintf('%s/tiles/pmtiles/%s.pmtiles', variable('maps_data_folder'), $name);
        default:
            throw new \InvalidArgumentException(sprintf('Unknown type %s', $type));
    }
}

/**
 * @return string[]
 */
function get_mbtiles_filenames(): array
{
    $mbtilesFolder = sprintf('%s/tiles/mbtiles', variable('maps_data_folder'));

    if (!fs()->exists($mbtilesFolder)) {
        return [];
    }

    $mbtiles = [];
    $files = glob(sprintf('%s/*.mbtiles', $mbtilesFolder));

    foreach ($files as $file) {
        $mbtiles[] = basename($file, '.mbtiles');
    }

    return $mbtiles;
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

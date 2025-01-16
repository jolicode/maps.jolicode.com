<?php

namespace maps\pbf;

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsTask;

use function Castor\fs;
use function Castor\io;
use function Castor\variable;
use function maps\utilities\download;
use function maps\utilities\get_tile_files_list;

#[AsTask(description: 'Get source PBF data', name: 'download')]
function downloadPbf(
    #[AsArgument(description: 'Name of the region to download')]
    string $name,
    bool $force = false,
): void
{
    if ($name === 'world') {
        $source = 'https://planet.openstreetmap.org/pbf/planet-latest.osm.pbf';
    } else {
        $source = sprintf('https://download.geofabrik.de/%s-latest.osm.pbf', $name);
    }

    io()->title(sprintf('Downloading PBF data for "%s" from %s', $name, $source));
    $targetFilename = sprintf('%s/data/tiles/pbf/%s.osm.pbf', variable('maps_data_folder'), basename($name));

    if (false === $force && fs()->exists($targetFilename)) {
        io()->warning('The file already exists, skipping download.');

        return;
    }

    download($source, $targetFilename);
    io()->success(sprintf('Downloaded %s successfully!', $name));
}

#[AsTask(description: 'List PBF files', name: 'list')]
function listPbf(): void {
    io()->title('Available PBF files');
    $files = get_tile_files_list('pbf');

    io()->table(
        ['filename', 'size', 'created at'],
        array_map(
            static function (string $file) {
                return [
                    basename($file),
                    filesize($file),
                    date('Y-m-d H:i:s', filectime($file)),
                ];
            },
            $files
        )
    );
}

#[AsTask(description: 'Delete PBF files')]
function delete(
    #[AsArgument(description: 'Name of the tile files to delete', autocomplete: 'maps\utilities\get_pbf_filenames')]
    ?string $name = null,
): void {
    if (null === $name) {
        io()->error('You must provide a name to delete. Pass "all" to remove all the files of the selected type.');

        return;
    }

    io()->title('Removing PBF files');
    $files = get_tile_files_list('pbf');

    if ('all' !== $name) {
        $files = array_filter(
            $files,
            static fn (string $file) => $name === basename($file, '.osm.pbf')
        );
    }

    foreach ($files as $file) {
        fs()->remove($file);
    }

    io()->info('Removed the following files:');
    io()->listing($files);
}

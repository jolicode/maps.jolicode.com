<?php

namespace maps\mbtiles;

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;

use function Castor\fs;
use function Castor\io;
use function Castor\run;
use function Castor\variable;
use function maps\utilities\create_directories;
use function maps\utilities\get_filepath;

#[AsTask(description: 'Generate mbtiles file')]
function generate(
    #[AsArgument(description: 'Name of the region to convert from pbf to mbtiles')]
    ?string $name = 'world',
    #[AsOption(description: 'Name of the layers schema to use')]
    ?string $schema = 'openmaptiles',
    ?bool $force = false,
    ?string $targetName = null,
): void {
    $pbfFilename = get_filepath($name, 'pbf');

    if (!fs()->exists($pbfFilename)) {
        io()->error(sprintf('The PBF file does not exist. Run `castor maps:pbf:download %s` first.', $name));

        return;
    }

    $targetFilename = get_filepath($targetName ?? $name, 'mbtiles');

    if (false === $force && fs()->exists($targetFilename)) {
        io()->warning('The file already exists, skipping conversion.');

        return;
    }

    create_directories();
    $storeDirectory = sprintf('%s/tmp/store/%s', variable('maps_data_folder'), uniqid());

    if (!fs()->exists($storeDirectory)) {
        fs()->mkdir($storeDirectory);
    }

    $command = [
        'tilemaker',
        '--input',
        $pbfFilename,
        '--output',
        $targetFilename,
        '--store',
        $storeDirectory,
        '--shard-stores',
        '--bbox',
        '-180,-90,180,90',
    ];

    if ($schema === 'openmaptiles') {
        $command[] = '--process';
        $command[] = sprintf('%s/tilemaker/process-openmaptiles.lua', variable('maps_data_folder'));

        $command[] = '--config';
        $command[] = sprintf('%s/tilemaker/config-openmaptiles.json', variable('maps_data_folder'));
    } elseif ($schema === 'shortbread') {
        $command[] = '--process';
        $command[] = sprintf('%s/resources/shortbread-tilemaker-main/process.lua', variable('maps_data_folder'));

        $command[] = '--config';
        $command[] = sprintf('%s/resources/shortbread-tilemaker-main/config.json', variable('maps_data_folder'));
    } else {
        $command[] = '--process';
        $command[] = sprintf('%s/tilemaker/protomaps-basemap.lua', variable('maps_data_folder'));

        $command[] = '--config';
        $command[] = sprintf('%s/tilemaker/protomaps-basemap.json', variable('maps_data_folder'));
    }

    run($command);
    io()->success(sprintf('mbtiles for "%s" generated successfully!', $name));
}


#[AsTask(description: 'Convert a mbtiles to its pmtiles equivalent', namespace: 'maps:mbtiles', name: 'convert')]
function convert(
    #[AsArgument(description: 'Name of the region to convert from mbtiles to pmtiles', autocomplete: 'maps\utilities\get_mbtiles_filenames')]
    ?string $name = 'world',
    ?string $targetName = null,
    bool $force = false,
): void {
    $mbtilesFilename = get_filepath($name, 'mbtiles');
    $targetFilename = get_filepath($targetName ?? $name, 'pmtiles');
    create_directories();

    if (!fs()->exists($mbtilesFilename)) {
        io()->error(sprintf('The MBTiles file does not exist. Run `castor maps:mbtiles:generate %s` first.', $name));

        return;
    }

    if (false === $force && fs()->exists($targetFilename)) {
        io()->warning('The file already exists, skipping conversion.');

        return;
    }

    $command = [
        variable('maps_data_folder') . '/bin/pmtiles/pmtiles',
        'convert',
        $mbtilesFilename,
        $targetFilename,
    ];

    run($command);
    io()->success(sprintf('Successfully converted %s into %s', $mbtilesFilename, $targetFilename));
}

<?php

namespace maps\tiles;

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsTask;
use Symfony\Component\Console\Completion\CompletionInput;

use function Castor\fs;
use function Castor\io;
use function Castor\variable;
use function maps\infra\docker_run;
use function maps\utilities\create_directories;
use function maps\utilities\get_tile_filepath;
use function maps\utilities\get_tile_files_list;

const TILE_FORMATS = ['mbtiles', 'pmtiles'];
const TILE_SCHEMAS = ['openmaptiles', 'shortbread', 'protomaps-basemaps'];

#[AsTask(description: 'Generate mbtiles file')]
function generate(
    #[AsArgument(description: 'Name of the region to convert from pbf to mbtiles', autocomplete: 'maps\utilities\get_pbf_filenames')]
    string $name,
    #[AsArgument(description: 'Name of the layers schema to use', autocomplete: TILE_SCHEMAS)]
    string $schema,
    ?bool $force = false,
    ?string $targetName = null,
): void {
    if (!fs()->exists(sprintf('%s/data/tiles/pbf/%s.osm.pbf', variable('maps_data_folder'), $name))) {
        io()->error(sprintf('The PBF file does not exist. Run `castor maps:pbf:download %s` first.', $name));

        return;
    }

    if (false === $force && fs()->exists(get_tile_filepath($schema, $targetName ?? $name, 'mbtiles'))) {
        io()->warning('The file already exists, skipping conversion.');

        return;
    }

    create_directories();
    $storeDirectory = sprintf('data/tmp/store/%s', uniqid());
    fs()->mkdir(sprintf('%s/%s', variable('maps_data_folder'), $storeDirectory));

    $outputFileDirectory = dirname(get_tile_filepath($schema, $targetName ?? $name, 'mbtiles'));

    if (!fs()->exists($outputFileDirectory)) {
        fs()->mkdir($outputFileDirectory);
    }

    $command = [
        'tilemaker',
        '--input',
        sprintf('data/tiles/pbf/%s.osm.pbf', $name),
        '--output',
        get_tile_filepath($schema, $targetName ?? $name, 'mbtiles', true),
        '--store',
        $storeDirectory,
        '--shard-stores',
        '--bbox',
        '-180,-90,180,90',
        '--config',
        'tilemaker-configs/' . $schema . '.json',
        '--process',
        'tilemaker-configs/' . $schema . '.lua',
    ];
    docker_run(implode(' ', $command));
    docker_run('rm -rf ' . $storeDirectory);

    io()->success(sprintf('mbtiles for "%s" generated successfully!', $name));
}


#[AsTask(description: 'Convert a mbtiles to its pmtiles equivalent')]
function convert(
    #[AsArgument(description: 'Name of the region to convert from mbtiles to pmtiles', autocomplete: 'maps\utilities\get_mbtiles_filenames')]
    ?string $name,
    ?string $targetName = null,
    bool $force = false,
): void {
    $name = explode('/', $name);

    if (count($name) !== 2) {
        io()->error('The name must be in the format "schema/name"');

        return;
    }

    [$schema, $name] = $name;
    $mbtilesFilename = get_tile_filepath($schema, $name, 'mbtiles');
    $targetFilename = get_tile_filepath($schema, $targetName ?? $name, 'pmtiles');
    create_directories();

    if (!fs()->exists($mbtilesFilename)) {
        io()->error(sprintf('The mmtiles file does not exist. Run `castor maps:mbtiles:generate %s` first.', $name));

        return;
    }

    $outputFileDirectory = dirname(get_tile_filepath($schema, $name, 'pmtiles'));

    if (!fs()->exists($outputFileDirectory)) {
        fs()->mkdir($outputFileDirectory);
    }

    if (false === $force && fs()->exists($targetFilename)) {
        io()->warning('The file already exists, skipping conversion.');

        return;
    }

    $command = [
        'data/bin/pmtiles/pmtiles',
        'convert',
        get_tile_filepath($schema, $name, 'mbtiles', true),
        get_tile_filepath($schema, $name, 'pmtiles', true),
    ];

    docker_run(implode(' ', $command));
    io()->success(sprintf('Successfully converted %s into %s', $mbtilesFilename, $targetFilename));
}

#[AsTask(description: 'List tiles', name: 'list')]
function listTiles(
    #[AsArgument(description: 'Type of the tile files to list', autocomplete: TILE_FORMATS)]
    ?string $type = null,
): void {
    $availableTypes = TILE_FORMATS;

    if (null !== $type) {
        if (!in_array($type, TILE_FORMATS, true)) {
            io()->error(sprintf('Unknown type "%s". Available types are: %s', $type, implode(', ', TILE_FORMATS)));

            return;
        }

        $availableTypes = [$type];
    }

    foreach ($availableTypes as $type) {
        io()->title(sprintf('%s files', $type));
        $files = get_tile_files_list($type);

        io()->table(
            ['schema', 'filename', 'size', 'created at'],
            array_map(
                static function (string $file) use ($type): array {
                    return [
                        basename(dirname($file)),
                        basename($file, '.' . $type),
                        filesize($file),
                        date('Y-m-d H:i:s', filemtime($file)),
                    ];
                },
                $files
            )
        );
    }
}

#[AsTask(description: 'Delete tile files', name: 'delete')]
function deleteTiles(
    #[AsArgument(description: 'Type of the tile files to delete', autocomplete: TILE_FORMATS)]
    ?string $type = null,
    #[AsArgument(description: 'Name of the tile files to delete', autocomplete: 'maps\tiles\get_tiles_filenames')]
    ?string $name = null,
): void {
    if (!in_array($type, TILE_FORMATS, true) && 'all' !== $type) {
        io()->error(sprintf('Unknown type "%s". Available types are: %s. Pass "all" to remove all the file types.', $type, implode(', ', TILE_FORMATS)));

        return;
    }

    if (null === $name) {
        io()->error('You must provide a name to delete. Pass "all" to remove all the files of the selected type.');

        return;
    }

    if ('all' === $type) {
        $types = TILE_FORMATS;
    } else {
        $types = [$type];
    }

    foreach ($types as $type) {
        io()->title(sprintf('Removing %s files', $type));
        $files = get_tile_files_list($type);

        if ('all' !== $name) {
            $files = array_filter(
                $files,
                static fn (string $file) => $name === basename(dirname($file)) . '/' . basename($file, '.' . $type)
            );
        }

        if (empty($files)) {
            io()->warning(sprintf('No %s files found to delete.', $type));

            continue;
        }

        foreach ($files as $file) {
            fs()->remove($file);
        }

        io()->info('Removed the following files:');
        io()->listing($files);
    }
}

function get_tiles_filenames(CompletionInput $input): array {
    $type = $input->getArgument('type');
    $filenames = [];

    if ('all' === $type) {
        $files = array_merge(get_tile_files_list('mbtiles'), get_tile_files_list('pmtiles'));
    } else {
        $files = get_tile_files_list($type);
    }

    foreach ($files as $file) {
        $parts = pathinfo($file);
        $filenames[] = basename($parts['dirname']) . '/' . $parts['filename'];
    }

    return array_unique($filenames);
}

<?php

namespace maps\styles;

use Castor\Attribute\AsTask;

use function Castor\fs;
use function Castor\io;
use function Castor\variable;
use function maps\infra\docker_run;
use function maps\utilities\create_directories;
use function maps\utilities\download;
use function maps\utilities\unarchive;

#[AsTask(description: 'Download all styles sources', name: 'all', namespace: 'maps:styles:download')]
function download_all(): void
{
    download_openmaptiles();
    download_protomaps_basemaps();
    download_versatiles();
}

#[AsTask(description: 'Download openmaptiles styles', name: 'openmaptiles', namespace: 'maps:styles:download')]
function download_openmaptiles(bool $force = false): void
{
    io()->info('Downloading openmaptiles styles');
    create_directories();

    $repositories = [
        'maptiler-basic-gl-style' => 'https://github.com/openmaptiles/maptiler-basic-gl-style/archive/refs/heads/master.zip',
        'osm-bright-gl-style' => 'https://github.com/openmaptiles/osm-bright-gl-style/archive/refs/heads/master.zip',
    ];

    foreach ($repositories as $name => $repository) {
        $targetFilename = sprintf('%s/data/resources/styles/%s.zip', variable('maps_data_folder'), $name);

        if (!$force && fs()->exists($targetFilename)) {
            io()->warning(sprintf('The file %s already exists, skipping download.', $targetFilename));

            continue;
        }

        download($repository, $targetFilename);
        unarchive($targetFilename, sprintf('%s/data/resources/styles', variable('maps_data_folder')));
    }

    io()->success('Downloaded openmaptiles styles styles successfully!');
}

#[AsTask(description: 'Download protomaps basemaps styles', name: 'protomaps-basemaps', namespace: 'maps:styles:download')]
function download_protomaps_basemaps(bool $force = false): void
{
    io()->info('Downloading protomaps basemaps styles');
    create_directories();

    $targetFilename = sprintf('%s/data/resources/styles/protomaps.zip', variable('maps_data_folder'));

    if (!$force && fs()->exists($targetFilename)) {
        io()->warning(sprintf('The file %s already exists, skipping download.', $targetFilename));

        return;
    }

    download('https://github.com/protomaps/basemaps/archive/refs/heads/main.zip', $targetFilename);
    unarchive($targetFilename, sprintf('%s/data/resources/styles', variable('maps_data_folder')));

    // build protomaps basemaps styles
    docker_run('npm install && npm run generate-styles pmtiles:///pmtiles', workDir: '/home/app/maps/data/resources/styles/basemaps-main/styles');
    io()->success('Downloaded and built protomaps basemaps styles successfully!');
}

#[AsTask(description: 'Download versatiles styles', name: 'versatiles', namespace: 'maps:styles:download')]
function download_versatiles(bool $force = false): void
{
    io()->info('Downloading versatiles styles');
    create_directories();
    $gzFilename = sprintf('%s/data/resources/styles/versatiles-style-latest.tar.gz', variable('maps_data_folder'));

    if (!$force && fs()->exists($gzFilename)) {
        io()->warning(sprintf('The file %s already exists, skipping download.', $gzFilename));

        return;
    }

    download(
        'https://github.com/versatiles-org/versatiles-style/releases/latest/download/styles.tar.gz',
        $gzFilename,
    );
    unarchive($gzFilename, sprintf('%s/data/resources/styles/versatiles-style', variable('maps_data_folder')));
    io()->success('Downloaded versatiles styles successfully!');
}

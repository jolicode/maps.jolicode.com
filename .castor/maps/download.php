<?php

namespace maps\download;

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsTask;

use function Castor\context;
use function Castor\fs;
use function Castor\io;
use function Castor\run;
use function Castor\variable;
use function maps\utilities\create_directories;
use function maps\utilities\download;
use function maps\utilities\get_filepath;
use function maps\utilities\unarchive;

#[AsTask(description: 'Download pmtiles', name: 'pmtiles', namespace: 'maps:download:binaries')]
function binaries_pmtiles(): void
{
    io()->info('Downloading pmtiles');
    create_directories();
    $gzFilename = sprintf('%s/bin/go-pmtiles.tar.gz', variable('maps_data_folder'));
    download(
        'https://github.com/protomaps/go-pmtiles/releases/download/v1.23.1/go-pmtiles_1.23.1_Linux_x86_64.tar.gz',
        $gzFilename,
    );
    unarchive($gzFilename, sprintf('%s/bin/pmtiles', variable('maps_data_folder')));

    io()->success('Downloaded pmtiles successfully!');
}

#[AsTask(description: 'Get source PBF data')]
function pbf(
    #[AsArgument(description: 'Name of the region to download')]
    ?string $name = 'world',
    bool $force = false,
): void
{
    if ($name === 'world') {
        $source = 'https://planet.openstreetmap.org/pbf/planet-latest.osm.pbf';
    } else {
        $source = sprintf('https://download.geofabrik.de/%s-latest.osm.pbf', $name);
    }

    io()->title(sprintf('Downloading PBF data for "%s" from %s', $name, $source));
    $targetFilename = get_filepath(basename($name), 'pbf');

    if (false === $force && fs()->exists($targetFilename)) {
        io()->warning('The file already exists, skipping download.');

        return;
    }

    download($source, $targetFilename);
    io()->success(sprintf('Downloaded %s successfully!', $name));
}

#[AsTask(description: 'Download shapefiles', name: 'shapefiles', namespace: 'maps:download:resources')]
function resources_shapefiles(): void
{
    io()->title('Downloading shapefiles');
    create_directories();
    $urls = [
        // 'https://www.naturalearthdata.com/http//www.naturalearthdata.com/download/10m/cultural/ne_10m_admin_0_countries.zip',
        // 'https://www.naturalearthdata.com/http//www.naturalearthdata.com/download/10m/cultural/ne_50m_admin_0_countries.zip',
        // 'https://osmdata.openstreetmap.de/download/land-polygons-split-4326.zip',
        'https://osmdata.openstreetmap.de/download/water-polygons-split-4326.zip',
        'https://osmdata.openstreetmap.de/download/simplified-water-polygons-split-3857.zip',
    ];

    foreach ($urls as $url) {
        $targetFilename = sprintf('%s/resources/shapefiles/%s.zip', variable('maps_data_folder'), basename($url));

        if (fs()->exists($targetFilename)) {
            io()->warning(sprintf('The file %s already exists, skipping download.', $targetFilename));

            continue;
        }

        io()->info(sprintf('Downloading %s', $url));
        download($url, $targetFilename);
        unarchive($targetFilename, sprintf('%s/resources/shapefiles', variable('maps_data_folder')));
    }

    $directory = sprintf('%s/resources/shapefiles/simplified-water-polygons-split-4326', variable('maps_data_folder'));

    if (!fs()->exists($directory)) {
        fs()->mkdir($directory);
    }

    $command = [
        'ogr2ogr',
        '-f',
        'ESRI Shapefile',
        sprintf('%s/resources/shapefiles/simplified-water-polygons-split-4326/simplified_water_polygons.shp', variable('maps_data_folder')),
        sprintf('%s/resources/shapefiles/simplified-water-polygons-split-3857/simplified_water_polygons.shp', variable('maps_data_folder')),
        '-t_srs',
        'EPSG:4326',
        '-lco',
        'ENCODING=utf8',
    ];
    run($command);
    io()->success('Downloaded and converted shapefiles successfully!');
}

#[AsTask(description: 'Download shortbread tilemaker config', name: 'shortbread-tilemaker', namespace: 'maps:download:resources')]
function resources_shortbread_tilemaker(): void
{
    io()->info('Downloading shortbread-tilemaker');
    create_directories();
    $targetFilename = sprintf('%s/resources/shortbread-tilemaker.zip', variable('maps_data_folder'));
    download('https://github.com/shortbread-tiles/shortbread-tilemaker/archive/refs/heads/main.zip', $targetFilename);
    unarchive($targetFilename, sprintf('%s/resources', variable('maps_data_folder')));

    io()->success('Downloaded shortbread-tilemaker successfully!');
}

#[AsTask(description: 'Download all styles sources', name: 'all', namespace: 'maps:download:styles')]
function styles_all(): void
{
    styles_openmaptiles();
    styles_protomaps_basemaps();
    styles_versatiles();
}

#[AsTask(description: 'Download openmaptiles styles', name: 'openmaptiles', namespace: 'maps:download:styles')]
function styles_openmaptiles(): void
{
    io()->info('Downloading openmaptiles styles');
    create_directories();

    $repositories = [
        'maptiler-basic-gl-style' => 'https://github.com/openmaptiles/maptiler-basic-gl-style/archive/refs/heads/master.zip',
        'osm-bright-gl-style' => 'https://github.com/openmaptiles/osm-bright-gl-style/archive/refs/heads/master.zip',
    ];

    foreach ($repositories as $name => $repository) {
        $targetFilename = sprintf('%s/resources/styles/%s.zip', variable('maps_data_folder'), $name);

        if (fs()->exists($targetFilename)) {
            io()->warning(sprintf('The file %s already exists, skipping download.', $targetFilename));

            continue;
        }

        download($repository, $targetFilename);
        unarchive($targetFilename, sprintf('%s/resources/styles', variable('maps_data_folder')));
    }

    io()->success('Downloaded openmaptiles styles styles successfully!');
}

#[AsTask(description: 'Download protomaps basemaps styles', name: 'protomaps-basemaps', namespace: 'maps:download:styles')]
function styles_protomaps_basemaps(): void
{
    io()->info('Downloading protomaps basemaps styles');
    create_directories();

    $targetFilename = sprintf('%s/resources/styles/protomaps.zip', variable('maps_data_folder'));

    if (fs()->exists($targetFilename)) {
        io()->warning(sprintf('The file %s already exists, skipping download.', $targetFilename));

        return;
    }

    download('https://github.com/protomaps/basemaps/archive/refs/heads/main.zip', $targetFilename);
    unarchive($targetFilename, sprintf('%s/resources/styles', variable('maps_data_folder')));

    // build protomaps basemaps styles
    $command = [
        'npm',
        'install',
    ];
    run($command, context: context()->withWorkingDirectory(sprintf('%s/resources/styles/basemaps-main/styles', variable('maps_data_folder'))));
    $command = [
        'npm',
        'run',
        'generate-styles',
        'pmtiles:///pmtiles',
    ];
    run($command, context: context()->withWorkingDirectory(sprintf('%s/resources/styles/basemaps-main/styles', variable('maps_data_folder'))));

    io()->success('Downloaded and built protomaps basemaps styles successfully!');
}

#[AsTask(description: 'Download versatiles styles', name: 'versatiles', namespace: 'maps:download:styles')]
function styles_versatiles(): void
{
    io()->info('Downloading versatiles styles');
    create_directories();
    $gzFilename = sprintf('%s/resources/styles/versatiles-style-latest.tar.gz', variable('maps_data_folder'));
    download(
        'https://github.com/versatiles-org/versatiles-style/releases/latest/download/styles.tar.gz',
        $gzFilename,
    );
    unarchive($gzFilename, sprintf('%s/resources/styles/versatiles-style', variable('maps_data_folder')));

    io()->success('Downloaded versatiles styles successfully!');
}

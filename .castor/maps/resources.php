<?php

namespace maps\resources;

use Castor\Attribute\AsTask;

use function Castor\fs;
use function Castor\io;
use function Castor\variable;
use function maps\infra\docker_run;
use function maps\utilities\create_directories;
use function maps\utilities\download;
use function maps\utilities\unarchive;


#[AsTask(description: 'Download all resources files', name: 'all', namespace: 'maps:resources:download')]
function download_all(): void
{
    download_shapefiles();
    download_shortbread_tilemaker();
    download_openmaptiles_tilemaker();
}

#[AsTask(description: 'Download shapefiles', name: 'shapefiles', namespace: 'maps:resources:download')]
function download_shapefiles(): void
{
    io()->title('Downloading shapefiles');
    create_directories();
    $urls = [
        'https://naciscdn.org/naturalearth/10m/physical/ne_10m_antarctic_ice_shelves_polys.zip',
        'https://naciscdn.org/naturalearth/10m/cultural/ne_10m_urban_areas.zip',
        'https://naciscdn.org/naturalearth/10m/physical/ne_10m_glaciated_areas.zip',
        'https://osmdata.openstreetmap.de/download/water-polygons-split-4326.zip',
        'https://osmdata.openstreetmap.de/download/simplified-water-polygons-split-3857.zip',
    ];

    foreach ($urls as $url) {
        $targetFilename = sprintf('%s/data/resources/shapefiles/%s.zip', variable('maps_data_folder'), basename($url));

        if (fs()->exists($targetFilename)) {
            io()->warning(sprintf('The file %s already exists, skipping download.', $targetFilename));

            continue;
        }

        io()->info(sprintf('Downloading %s', $url));
        download($url, $targetFilename);
        unarchive($targetFilename, sprintf('%s/data/resources/shapefiles', variable('maps_data_folder')));
    }

    $wgs84SimplifiedFilename = sprintf('%s/data/resources/shapefiles/simplified-water-polygons-split-4326/simplified_water_polygons.shp', variable('maps_data_folder'));

    if (fs()->exists($wgs84SimplifiedFilename)) {
        io()->warning('The WGS84 simplified water polygons file already exists, skipping conversion.');

        return;
    }

    if (!fs()->exists(dirname($wgs84SimplifiedFilename))) {
        fs()->mkdir(dirname($wgs84SimplifiedFilename));
    }

    docker_run(sprintf(
        'ogr2ogr -f "ESRI Shapefile" %s %s -t_srs EPSG:4326 -lco ENCODING=utf8',
        'data/resources/shapefiles/simplified-water-polygons-split-4326/simplified_water_polygons.shp',
        'data/resources/shapefiles/simplified-water-polygons-split-3857/simplified_water_polygons.shp',
    ));
    io()->success('Downloaded and converted shapefiles successfully!');
}

#[AsTask(description: 'Download shortbread tilemaker config', name: 'shortbread', namespace: 'maps:resources:download')]
function download_shortbread_tilemaker(): void
{
    download_shapefiles();
    io()->info('Downloading shortbread-tilemaker');
    $targetFilename = sprintf('%s/data/resources/shortbread-tilemaker.zip', variable('maps_data_folder'));

    if (fs()->exists($targetFilename)) {
        io()->warning(sprintf('The file %s already exists, skipping download.', $targetFilename));
    } else {
        download('https://github.com/shortbread-tiles/shortbread-tilemaker/archive/refs/heads/main.zip', $targetFilename);
        unarchive($targetFilename, sprintf('%s/data/resources', variable('maps_data_folder')));
    }

    fs()->copy(
        sprintf('%s/data/resources/shortbread-tilemaker-main/process.lua', variable('maps_data_folder')),
        sprintf('%s/tilemaker-configs/shortbread.lua', variable('maps_data_folder')),
    );
    $shortbreadConfig = json_decode(file_get_contents(sprintf('%s/data/resources/shortbread-tilemaker-main/config.json', variable('maps_data_folder'))), true);
    $shortbreadConfig['layers']['boundary_labels']['source'] = 'data/resources/shortbread-tilemaker-main/' . $shortbreadConfig['layers']['boundary_labels']['source'];
    $shortbreadConfig['layers']['ocean']['source'] = 'data/resources/shapefiles/water-polygons-split-4326/water_polygons.shp';
    $shortbreadConfig['layers']['ocean-low']['source'] = 'data/resources/shapefiles/simplified-water-polygons-split-4326/simplified_water_polygons.shp';
    file_put_contents(
        sprintf('%s/tilemaker-configs/shortbread.json', variable('maps_data_folder')),
        json_encode($shortbreadConfig, JSON_PRETTY_PRINT),
    );

    io()->success('Downloaded shortbread tilemaker config successfully!');
}

#[AsTask(description: 'Download openmaptiles tilemaker config', name: 'openmaptiles', namespace: 'maps:resources:download')]
function download_openmaptiles_tilemaker(): void
{
    download_shapefiles();
    io()->info('Downloading tilemaker');
    $targetFilename = sprintf('%s/data/resources/tilemaker.zip', variable('maps_data_folder'));

    if (fs()->exists($targetFilename)) {
        io()->warning(sprintf('The file %s already exists, skipping download.', $targetFilename));
    } else {
        download('https://github.com/systemed/tilemaker/archive/refs/tags/v3.0.0.zip', $targetFilename);
        unarchive($targetFilename, sprintf('%s/data/resources', variable('maps_data_folder')));
    }

    fs()->copy(
        sprintf('%s/data/resources/tilemaker-3.0.0/resources/process-openmaptiles.lua', variable('maps_data_folder')),
        sprintf('%s/tilemaker-configs/openmaptiles.lua', variable('maps_data_folder')),
    );
    $shortbreadConfig = json_decode(file_get_contents(sprintf('%s/data/resources/tilemaker-3.0.0/resources/config-openmaptiles.json', variable('maps_data_folder'))), true);
    $shortbreadConfig['layers']['ocean']['source'] = 'data/resources/shapefiles/water-polygons-split-4326/water_polygons.shp';
    $shortbreadConfig['layers']['urban_areas']['source'] = 'data/resources/shapefiles/ne_10m_urban_areas.shp';
    $shortbreadConfig['layers']['ice_shelf']['source'] = 'data/resources/shapefiles/ne_10m_antarctic_ice_shelves_polys.shp';
    $shortbreadConfig['layers']['glacier']['source'] = 'data/resources/shapefiles/ne_10m_glaciated_areas.shp';
    file_put_contents(
        sprintf('%s/tilemaker-configs/openmaptiles.json', variable('maps_data_folder')),
        json_encode($shortbreadConfig, JSON_PRETTY_PRINT),
    );

    io()->success('Downloaded openmaptiles tilemaker config successfully!');
}

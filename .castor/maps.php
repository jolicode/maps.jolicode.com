<?php

namespace maps;

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;

use function Castor\finder;
use function Castor\http_download;
use function Castor\io;
use function Castor\run;
use function Castor\variable;

const DIRECTORY_PBF = 'data/pbf';
const DIRECTORY_PMTILES = 'data/pmtiles';

#[AsTask(description: 'Get source PBF data for the world, from OSM Planet', namespace: 'maps', name: 'osm-planet-download')]
function planetOsmDownload(): void
{
    io()->title('Downloading OSM Planet PBF data');

    $progressBar = io()->createProgressBar();

    $divider = 1;
    http_download('https://planet.openstreetmap.org/pbf/planet-latest.osm.pbf', 'data/pbf/planet.osm.pbf', options: [
        'headers' => [
            'User-Agent' => 'cartos',
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

    io()->success('Download completed!');
}

#[AsTask(description: 'Converts the PBF data to PMTiles', namespace: 'maps', name: 'convert')]
function convertPbfToPmtiles(
    #[AsArgument(description: 'Path to the file to be converted.', autocomplete: 'maps\get_source_pbf_filenames')]
    string $filename,
    #[AsOption(description: 'The config file to use.')]
    ?string $config = null,
    #[AsOption(description: 'The Lua processing script to use.')]
    ?string $luaScript = null,
    #[AsOption(description: 'Path of the output file. If not set, the output filename will be drived from the input filename.')]
    ?string $output = null,
    #[AsOption(description: 'Bounding box to use.')]
    ?string $bbox = null,
): void
{
    io()->title('Converting PBF data to PMTiles');

    if (false === file_exists(variable('root_dir') . '/' . DIRECTORY_PBF . '/' . $filename)) {
        throw new \RuntimeException(sprintf('File "%s" does not exist', variable('root_dir') . '/' . DIRECTORY_PBF . '/' . $filename));
    }

    $outputFilename = $output ?? pathinfo($filename, PATHINFO_FILENAME) . '.pmtiles';
    // $command = [
    //     'docker',
    //     'run',
    //     '--memory=16g',
    //     '-it',
    //     '--rm',
    //     '-v',
    //     sprintf('%s/data:/data', variable('root_dir')),
    //     '-v',
    //     sprintf('%s/tilemaker:/tilemaker', variable('root_dir')),
    //     'ghcr.io/systemed/tilemaker:master',
    //     '/' . DIRECTORY_PBF . '/' . $filename,
    //     '--output',
    //     '/' . DIRECTORY_PMTILES . '/' . $outputFilename,
    //     '--store',
    //     '/data/tmp/store',
    //     '--shard-stores',
    // ];

    $command = [
        'tilemaker',
        variable('root_dir').'/' . DIRECTORY_PBF . '/' . $filename,
        '--output',
        variable('root_dir').'/' . DIRECTORY_PMTILES . '/' . $outputFilename,
        '--store',
        variable('root_dir').'/data/tmp/store',
        '--shard-stores',
    ];

    if (null !== $bbox) {
        $command[] = '--bbox';
        $command[] = $bbox;
    }

    if (null !== $luaScript) {
        $command[] = '--process';
        $command[] = variable('root_dir').$luaScript;
    }

    if (null !== $config) {
        $command[] = '--config';
        $command[] = variable('root_dir').$config;
    }

    run($command);
    io()->success('Conversion completed!');
}

#[AsTask(description: 'Generate coastline for the world', namespace: 'maps', name: 'generate-coastline')]
function generateCoastlinePmtiles(): void
{
    convertPbfToPmtiles(
        filename: 'monaco-latest.osm.pbf',
        config: '/tilemaker/resources/config-coastline.json',
        luaScript: '/tilemaker/resources/process-coastline.lua',
        bbox: '-180,-85,180,85',
        output: 'monaco-coastline.pmtiles',
    );
    // convertPbfToPmtiles(
    //     filename: 'planet.osm.pbf',
    //     config: '/tilemaker/resources/config-coastline.json',
    //     luaScript: '/tilemaker/resources/process-coastline.lua',
    //     bbox: '-180,-85,180,85',
    //     output: 'planet-coastline.pmtiles',
    // );
}

// #[AsTask(description: 'Generate PMTiles for the world', namespace: 'maps', name: 'generate')]
// function generatePmtiles(): void
// {
//     convertPbfToPmtiles(
//         filename: 'planet.osm.pbf',
//         config: 'resources/config-coastline.json',
//         luaScript: 'resources/process-coastline.lua',
//         output: 'planet.pmtiles',
//     );
//     convertPbfToPmtiles(
//         filename: 'switzerland-latest.osm.pbf',
//         config: 'resources/config-openmaptiles.json',
//         luaScript: 'resources/process-openmaptiles.lua',
//         output: 'planet.pmtiles',
//         merge: true,
//     );
//     convertPbfToPmtiles(
//         filename: 'central-america-latest.osm.pbf',
//         config: 'resources/config-openmaptiles.json',
//         luaScript: 'resources/process-openmaptiles.lua',
//         output: 'planet.pmtiles',
//         merge: true,
//     );
//     convertPbfToPmtiles(
//         filename: 'france-latest.osm.pbf',
//         config: 'resources/config-openmaptiles.json',
//         luaScript: 'resources/process-openmaptiles.lua',
//         output: 'planet.pmtiles',
//         merge: true,
//     );
// }

function get_source_pbf_filenames(): array
{
    $pbfDirectory = realpath(variable('root_dir') . DIRECTORY_SEPARATOR . DIRECTORY_PBF);

    if (false === $pbfDirectory) {
        throw new \RuntimeException(sprintf('Directory "%s" does not exist', DIRECTORY_PBF));
    }

    $files = finder()->files()->in($pbfDirectory)->name('*.pbf')->sortByName();
    $filenames = [];

    foreach ($files as $file) {
        $filenames[] = $file->getRelativePathname();
    }

    return $filenames;
}

<?php

namespace App\Map;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Styles
{
    private const SCHEMA_SOURCE_NAMES = [
        'openmaptiles' => 'openmaptiles',
        'protomaps-basemaps' => 'protomaps',
        'shortbread' => 'versatiles-shortbread',
    ];
    private const SCHEMA_STYLES = [
        'openmaptiles' => ['basic', 'bright'],
        'protomaps-basemaps' => ['black', 'dark', 'grayscale', 'light', 'white'],
        'shortbread' => ['colorful', 'eclipse', 'graybeard', 'neutrino'],
    ];

    public function __construct(
        #[Autowire(env: 'resolve:DATA_DIRECTORY')]
        private readonly string $dataDirectory,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function getDefaultStyle(string $schema): string
    {
        return self::SCHEMA_STYLES[$schema][0];
    }

    public function getStyleContent(
        string $schema,
        string $location,
        string $style,
    ): ?\stdClass {
        switch ($schema) {
            case 'openmaptiles':
                if ('basic' === $style) {
                    $stylePath = 'resources/styles/maptiler-basic-gl-style-master/style.json';
                } else if ('bright' === $style) {
                    $stylePath = 'resources/styles/osm-bright-gl-style-master/style.json';
                } else {
                    return null;
                }
                break;
            case 'protomaps-basemaps':
                $stylePath = 'resources/styles/basemaps-main/styles/dist/styles/' . $style . '/fr.json';
                break;
            case 'shortbread':
                $stylePath = 'resources/styles/versatiles-style/' . $style . '.json';
                break;
            default:
                return null;
        }

        $stylePath = sprintf(
            '%s/%s',
            $this->dataDirectory,
            $stylePath,
        );

        if (!file_exists($stylePath)) {
            return null;
        }

        $sourceStyle = json_decode(file_get_contents($stylePath), false);
        $sourceStyle->sources = [
            self::SCHEMA_SOURCE_NAMES[$schema] => [
                'type' => 'vector',
                'url' => 'pmtiles://' . $this->urlGenerator->generate('map_pmtiles', [
                    'schema' => $schema,
                    'location' => $location,
                ]),
            ],
        ];

        return $sourceStyle;
    }
}

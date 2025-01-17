<?php

namespace App\Controller;

use App\Map\Styles;
use App\Map\TilesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MapController extends AbstractController
{
    public function __construct(
        #[Autowire(env: 'resolve:DATA_DIRECTORY')]
        private readonly string $dataDirectory,
        private readonly TilesRepository $tilesRepository,
        private readonly Styles $styles,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/{schema}/{location}/{style}', name: 'map')]
    public function index(
        string $schema,
        string $location,
        string $style,
    ): Response {
        if (!$this->tilesRepository->has($schema, $location)) {
            throw $this->createNotFoundException(sprintf('There is no map available for schema "%s" and location "%s".', $schema, $location));
        }

        return $this->render('map/index.html.twig', [
            'location' => $location,
            'schema' => $schema,
            'style' => $style,
            'sources' => $this->tilesRepository->list(),
            'availableStyles' => $this->styles->getAvailableStyles($schema),
        ]);
    }

    #[Route('/style/{schema}/{location}/{style}.json', name: 'map_style')]
    public function style(
        string $schema,
        string $location,
        string $style,
    ): JsonResponse {
        $styleContent = $this->styles->getStyleContent($schema, $location, $style);

        if (null === $styleContent) {
            throw $this->createNotFoundException();
        }

        $response = new JsonResponse($styleContent);
        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response;
    }

    #[Route('/pmtiles/{schema}/{location}.pmtiles', name: 'map_pmtiles')]
    public function pmtiles(
        string $schema,
        string $location,
    ): Response {
        throw $this->createNotFoundException(sprintf('There is no PMTiles file available for schema "%s" and location "%s".', $schema, $location));
    }
}

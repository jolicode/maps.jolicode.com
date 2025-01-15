<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MapController extends AbstractController
{
    #[Route('/{style}', name: 'map')]
    public function index(string $style = 'light'): Response
    {
        return $this->render('map/index.html.twig', [
            'style' => $style,
        ]);
    }

    #[Route('/style/{style}.json', name: 'style')]
    public function style(string $style = 'light'): JsonResponse
    {
        $sourceStyle = json_decode(
            file_get_contents(__DIR__ . "/../../var/basemaps/styles/dist/styles/{$style}/fr.json"),
            false,
        );
        $sourceStyle->sources->protomaps = [
            'type' => 'vector',
            'url' => 'https://martin.cartos.test/openmaptiles',
        ];
        $sourceStyle->layers[3]->filter = array_values(array_diff($sourceStyle->layers[3]->filter, ['protected_area']));

        return new JsonResponse($sourceStyle);
    }
}

<?php

namespace App\Controller;

use App\Map\Styles;
use App\Map\TilesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomepageController extends AbstractController
{
    public function __construct(
        private readonly TilesRepository $tilesRepository,
        private readonly Styles $styles,
    ) {
    }

    #[Route('/', name: 'homepage')]
    public function index(): Response
    {
        return $this->render('homepage/index.html.twig', [
            'sources' => $this->tilesRepository->list(),
            'styles' => $this->styles->getAvailableStyles(),
        ]);
    }
}

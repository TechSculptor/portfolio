<?php

namespace App\Controller;

use App\Entity\Starship;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// The StarshipController class provides a route f
// or displaying a single starship.
class StarshipController extends AbstractController
{
    // The show method retrieves a single starship by its slug
    // and renders it in the show template.
    #[Route('/starships/{slug}', name: 'app_starship_show')]
    public function show(#[MapEntity(mapping: ['slug' => 'slug'])] Starship $ship): Response
    {
        return $this->render('starship/show.html.twig', [
            'ship' => $ship,
        ]);
    }
}

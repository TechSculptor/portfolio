<?php

namespace App\Controller;

use App\Repository\StarshipRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// The StarshipApiController class provides API endpoints
// for retrieving starship data.
#[Route('/api/starships')]
class StarshipApiController extends AbstractController
{
    // The getCollection method retrieves all starships
    // from the repository and returns them as a JSON response.
    #[Route('', methods: ['GET'])]
    public function getCollection(StarshipRepository $repository): Response
    {
        $starships = $repository->findAll();

        return $this->json($starships);
    }

    // The get method retrieves a single starship by its ID
    // from the repository and returns it as a JSON response.
    #[Route('/{id<\d+>}', methods: ['GET'])]
    public function get(StarshipRepository $repository, int $id): Response
    {
        $starship = $repository->find($id);

        // If the starship is not found, throw a 404 error.
        if (!$starship) {
            throw $this->createNotFoundException('Starship not found');
        }

        return $this->json($starship);
    }
}

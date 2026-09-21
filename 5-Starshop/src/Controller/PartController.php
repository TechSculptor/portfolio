<?php

namespace App\Controller;

use App\Form\PartSearchType;
use App\Repository\StarshipPartRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PartController extends AbstractController
{
    #[Route('/parts', name: 'app_part_index')]
    public function index(StarshipPartRepository $repository, Request $request): Response
    {
        // Create the search form and handle the request
        $searchForm = $this->createForm(PartSearchType::class);
        // Initialize the query variable to an empty string
        $query = '';
        // Handle the request and check if the form is submitted and valid
        $searchForm->handleRequest($request);
        // If the form is submitted and valid, get the query from the form data
        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $query = $searchForm->get('query')->getData();
        }
        // Use the repository to find all parts ordered by price,
        // optionally filtered by the query
        $parts = $repository->findAllOrderedByPrice($query);

        // Render the index template with the parts and search form
        return $this->render('part/index.html.twig', [
            'parts' => $parts,
            'searchForm' => $searchForm,
        ]);
    }
}

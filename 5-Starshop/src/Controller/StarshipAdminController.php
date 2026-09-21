<?php

namespace App\Controller;

use App\Entity\Starship;
use App\Form\StarshipType;
use App\Repository\StarshipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/starship')]
final class StarshipAdminController extends AbstractController
{
    // The index method retrieves all starships 
    // from the repository and renders them in the index template.
    #[Route('', name: 'app_starship_admin_index', methods: ['GET'])]
    public function index(StarshipRepository $starshipRepository): Response
    {
        return $this->render('starship_admin/index.html.twig', [
            'starships' => $starshipRepository->findAll(),
        ]);
    }

    // The new method creates a new starship 
    // and handles the form submission.
    #[Route('/new', name: 'app_starship_admin_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $starship = new Starship();
        $form = $this->createForm(StarshipType::class, $starship);
        $form->handleRequest($request);

        // If the form is submitted and valid, persist the new starship
        // and redirect to the index page
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($starship);
            $entityManager->flush();

            return $this->redirectToRoute('app_starship_admin_index');
        }

        return $this->render('starship_admin/new.html.twig', [
            'form' => $form,
        ]);
    }

    // The show method retrieves a single starship 
    // from the repository and renders it in the show template.
    #[Route('/{id}', name: 'app_starship_admin_show', methods: ['GET'])]
    public function show(Starship $starship): Response
    {
        return $this->render('starship_admin/show.html.twig', [
            'starship' => $starship,
        ]);
    }

    // The edit method retrieves a single starship
    // from the repository, handles the form submission,
    // and updates the starship if the form is valid.
    #[Route('/{id}/edit', name: 'app_starship_admin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Starship $starship, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(StarshipType::class, $starship);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_starship_admin_index');
        }

        return $this->render('starship_admin/edit.html.twig', [
            'starship' => $starship,
            'form' => $form,
        ]);
    }

    // The delete method retrieves a single starship from the repository,
    // checks the CSRF token, and deletes the starship if the token is valid.
    #[Route('/{id}', name: 'app_starship_admin_delete', methods: ['POST'])]
    public function delete(Request $request, Starship $starship, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$starship->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($starship);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_starship_admin_index');
    }
}

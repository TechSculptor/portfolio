<?php

namespace App\Controller;

use App\Repository\StarshipRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// La classe AbstractController fournit des méthodes pratiques 
// pour générer des réponses, accéder aux services 
// et gérer les requêtes HTTP, et gérer les sessions, entre autres.
class MainController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function homepage(
        // La classe StarshipRepository fournit des métodes 
        // pour accéder aux données de la base de données.
        StarshipRepository $repository, 
        // La classe Request encapsule les informations de la requête HTTP.
        Request $request): Response
    {
        $ships = $repository->findIncompleteOrderedByDroidCount();
        $ships->setMaxPerPage(5);

        // La méthode getInt() récupère la valeur de la page depuis la requête HTTP,
        // et la convertit en entier. Si la valeur n'est pas présente,
        // elle retourne 1 par défaut.
        $ships->setCurrentPage($request->query->getInt('page', 1));
        // La méthode getCurrentPageResults() 
        // retourne un itérateur sur les résultats de la page courante.
        $shipsOnPage = iterator_to_array($ships->getCurrentPageResults());
        // La fonction array_rand() retourne une clé aléatoire 
        // du tableau $shipsOnPage.
        $myShip = $shipsOnPage ? $shipsOnPage[array_rand($shipsOnPage)] : null;

        // La méthode render() génère une réponse HTML 
        // en utilisant le moteur de templates Twig.
        return $this->render('main/homepage.html.twig', [
            'ships' => $ships,
            'myShip' => $myShip,
        ]);
    }
}

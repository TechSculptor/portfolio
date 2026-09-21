<?php

namespace App\Controller;

use App\Repository\StarshipRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// The AbstractController class provides handy methods
// to generate responses, access services
// and handle HTTP requests and sessions, among other things.
class MainController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function homepage(
        // The StarshipRepository class provides methods
        // to access the data of the database.
        StarshipRepository $repository,
        // The Request class encapsulates the information of the HTTP request.
        Request $request): Response
    {
        $ships = $repository->findIncompleteOrderedByDroidCount();
        $ships->setMaxPerPage(5);

        // The getInt() method reads the page value from the HTTP request
        // and converts it to an integer. If the value is not present,
        // it returns 1 by default.
        $ships->setCurrentPage($request->query->getInt('page', 1));
        // The getCurrentPageResults() method
        // returns an iterator over the results of the current page.
        $shipsOnPage = iterator_to_array($ships->getCurrentPageResults());
        // The array_rand() function returns a random key
        // of the $shipsOnPage array.
        $myShip = $shipsOnPage ? $shipsOnPage[array_rand($shipsOnPage)] : null;

        // The render() method generates an HTML response
        // using the Twig template engine.
        return $this->render('main/homepage.html.twig', [
            'ships' => $ships,
            'myShip' => $myShip,
        ]);
    }
}

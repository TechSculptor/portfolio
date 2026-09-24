<?php

namespace App\Tests\Controller;

use App\Factory\DroidFactory;
use App\Factory\StarshipFactory;
use App\Factory\StarshipPartFactory;
use App\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class StarshipControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    public function testStarshipDetailPageIsDisplayed(): void
    {
        $client = static::createClient();
        $ship = StarshipFactory::createOne(['name' => 'Nebula Test']);
        $client->loginUser(UserFactory::createOne());

        $client->request('GET', '/starships/'.$ship->getSlug());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Nebula Test');
    }

    public function testDetailPageListsPartsAndDroids(): void
    {
        $client = static::createClient();
        $ship = StarshipFactory::createOne();
        StarshipPartFactory::createOne(['starship' => $ship, 'name' => 'Cheap widget', 'price' => 100]);
        StarshipPartFactory::createOne(['starship' => $ship, 'name' => 'Golden flux capacitor', 'price' => 75000]);
        $ship->addDroid(DroidFactory::createOne(['name' => 'ZZZ-123']));
        static::getContainer()->get(EntityManagerInterface::class)->flush();
        $client->loginUser(UserFactory::createOne());

        $client->request('GET', '/starships/'.$ship->getSlug());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('#droids-title + ul', 'ZZZ-123');
        self::assertSelectorTextContains('#parts-title', 'Parts (2)');
        self::assertSelectorTextContains('#expensive-title', 'Expensive parts (1)');
        self::assertSelectorTextContains('#expensive-title + ul', 'Golden flux capacitor');
        self::assertSelectorTextNotContains('#expensive-title + ul', 'Cheap widget');
    }

    public function testDetailPageWithoutPartsNorDroidsShowsEmptyStates(): void
    {
        $client = static::createClient();
        $ship = StarshipFactory::createOne();
        $client->loginUser(UserFactory::createOne());

        $client->request('GET', '/starships/'.$ship->getSlug());

        self::assertSelectorTextContains('main, body', 'No droids on board.');
        self::assertSelectorTextContains('main, body', 'No parts assigned yet.');
    }

    public function testEditAndDeleteAreHiddenFromStandardUsers(): void
    {
        $client = static::createClient();
        $ship = StarshipFactory::createOne();
        $client->loginUser(UserFactory::createOne(['roles' => []]));

        $client->request('GET', '/starships/'.$ship->getSlug());

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('a[href$="/edit"]');
        self::assertSelectorNotExists('form[action^="/admin/"]');
    }

    public function testEditAndDeleteAreShownToAdministrators(): void
    {
        $client = static::createClient();
        $ship = StarshipFactory::createOne();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));

        $client->request('GET', '/starships/'.$ship->getSlug());

        self::assertSelectorExists('a[href$="/edit"]');
        self::assertSelectorExists('form[action^="/admin/"]');
    }

    public function testUnknownStarshipReturnsA404(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne());

        $client->request('GET', '/starships/does-not-exist');

        self::assertResponseStatusCodeSame(404);
    }
}

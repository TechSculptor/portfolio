<?php

namespace App\Tests\Controller;

use App\Factory\StarshipFactory;
use App\Factory\UserFactory;
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

    public function testUnknownStarshipReturnsA404(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne());

        $client->request('GET', '/starships/does-not-exist');

        self::assertResponseStatusCodeSame(404);
    }
}

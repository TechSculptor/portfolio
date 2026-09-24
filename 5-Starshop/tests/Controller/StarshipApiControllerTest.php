<?php

namespace App\Tests\Controller;

use App\Factory\StarshipFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class StarshipApiControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    public function testAnonymousUserIsRedirectedToLogin(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/starships');

        self::assertResponseRedirects('/login');
    }

    public function testCollectionReturnsAllStarshipsAsJson(): void
    {
        $client = static::createClient();
        StarshipFactory::createMany(3);
        $client->loginUser(UserFactory::createOne());

        $client->request('GET', '/api/starships');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertCount(3, json_decode($client->getResponse()->getContent(), true));
    }

    public function testOneStarshipIsReturnedById(): void
    {
        $client = static::createClient();
        $ship = StarshipFactory::createOne(['name' => 'Nebula Test']);
        $client->loginUser(UserFactory::createOne());

        $client->request('GET', '/api/starships/'.$ship->getId());

        self::assertResponseIsSuccessful();
        self::assertSame('Nebula Test', json_decode($client->getResponse()->getContent(), true)['name']);
    }

    public function testUnknownIdReturnsA404(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne());

        $client->request('GET', '/api/starships/999999');

        self::assertResponseStatusCodeSame(404);
    }

    public function testTheRoutePrefixIsNotDuplicated(): void
    {
        $client = static::createClient();
        $ship = StarshipFactory::createOne();
        $client->loginUser(UserFactory::createOne());

        $client->request('GET', '/api/starships/api/starships/'.$ship->getId());

        self::assertResponseStatusCodeSame(404);
    }
}

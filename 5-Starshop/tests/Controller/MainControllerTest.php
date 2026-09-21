<?php

namespace App\Tests\Controller;

use App\Factory\StarshipFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class MainControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    public function testLoggedInUserSeesTheShipRepairQueue(): void
    {
        $client = static::createClient();
        StarshipFactory::createMany(3);
        $client->loginUser(UserFactory::createOne());

        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Ship Repair Queue');
    }

    public function testHomepageDisplaysTheMockedIssLocation(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne());

        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h2', 'ISS Location');
        self::assertSelectorTextContains('body', 'daylight');
    }
}

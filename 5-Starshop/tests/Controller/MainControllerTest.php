<?php

namespace App\Tests\Controller;

use App\Factory\StarshipFactory;
use App\Factory\UserFactory;
use App\Model\StarshipStatusEnum;
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

    public function testShipNamesLinkToTheirDetailPage(): void
    {
        $client = static::createClient();
        $ship = StarshipFactory::createOne(['status' => StarshipStatusEnum::WAITING]);
        $client->loginUser(UserFactory::createOne());

        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists(sprintf('a[href="/starships/%s"]', $ship->getSlug()));
    }

    public function testShipStatusBadgeReflectsTheRealStatus(): void
    {
        $client = static::createClient();
        StarshipFactory::createOne(['status' => StarshipStatusEnum::WAITING]);
        $client->loginUser(UserFactory::createOne());

        $client->request('GET', '/');

        self::assertSelectorTextContains('main', 'waiting');
        self::assertSelectorTextNotContains('main', 'in progress');
    }

    public function testPaginationIsRenderedOnceBelowTheList(): void
    {
        $client = static::createClient();
        StarshipFactory::createMany(12, ['status' => StarshipStatusEnum::WAITING]);
        $client->loginUser(UserFactory::createOne());

        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, 'nav[aria-label="Pagination"]');
        self::assertSelectorTextContains('nav[aria-label="Pagination"]', 'Next');
    }

    public function testNavigationHasNoDeadContactLink(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne());

        $client->request('GET', '/');

        self::assertSelectorTextNotContains('header nav', 'Contact');
    }
}

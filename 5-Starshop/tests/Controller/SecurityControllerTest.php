<?php

namespace App\Tests\Controller;

use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SecurityControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    public function testAnonymousUserIsRedirectedToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseRedirects('/login');
    }

    public function testLoginPageRendersACsrfProtectedForm(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name="_csrf_token"][data-controller="csrf-protection"]');
    }

    public function testLoggedInUserIsNotShownTheLoginPage(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne());

        $client->request('GET', '/login');

        self::assertResponseRedirects('/');
    }

    public function testLoginIsThrottledAfterTooManyFailedAttempts(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => 'victim@example.com']);

        // 5 failed attempts are allowed, the 6th one is blocked
        for ($attempt = 1; $attempt <= 6; ++$attempt) {
            $client->request('POST', '/login', [
                '_username' => 'victim@example.com',
                '_password' => 'wrong-password-'.$attempt,
                // Same-origin stateless CSRF: the placeholder token is accepted when the browser
                // sends same-origin headers (what a real browser does)
                '_csrf_token' => 'csrf-token',
            ], [], ['HTTP_ORIGIN' => 'http://localhost', 'HTTP_SEC_FETCH_SITE' => 'same-origin']);
        }
        $client->followRedirect();

        self::assertSelectorTextContains('body', 'Too many failed login attempts');
    }

    public function testStandardUserCannotReachTheAdminArea(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => []]));

        $client->request('GET', '/admin/starship');

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminUserCanReachTheAdminArea(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne(['roles' => ['ROLE_ADMIN']]));

        $client->request('GET', '/admin/starship');

        self::assertResponseIsSuccessful();
    }
}

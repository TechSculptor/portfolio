<?php

namespace App\Tests\Twig\Runtime;

use App\Twig\Runtime\AppExtensionRuntime;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AppExtensionRuntimeTest extends TestCase
{
    private AppExtensionRuntime $runtime;

    protected function setUp(): void
    {
        $this->runtime = new AppExtensionRuntime(
            $this->createStub(HttpClientInterface::class),
            $this->createStub(CacheInterface::class),
        );
    }

    public function testAgoReturnsFallbackForNullDate(): void
    {
        self::assertSame('Not yet arrived', $this->runtime->ago(null));
    }

    public function testAgoFormatsAPastDate(): void
    {
        self::assertSame('2 hours ago', $this->runtime->ago(new \DateTimeImmutable('-2 hours')));
    }

    public function testAgoFormatsAFutureDate(): void
    {
        self::assertSame('in 3 days', $this->runtime->ago(new \DateTimeImmutable('+3 days')));
    }

    public function testAgoReturnsJustNowForTheCurrentMoment(): void
    {
        self::assertSame('just now', $this->runtime->ago(new \DateTimeImmutable()));
    }

    public function testAgoPluralizesSingleUnitsCorrectly(): void
    {
        self::assertSame('1 hour ago', $this->runtime->ago(new \DateTimeImmutable('-1 hour')));
    }
}

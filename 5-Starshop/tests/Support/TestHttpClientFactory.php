<?php

namespace App\Tests\Support;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class TestHttpClientFactory
{
    public static function create(): HttpClientInterface
    {
        return new MockHttpClient(new MockResponse(json_encode([
            'name' => 'iss',
            'id' => 25544,
            'latitude' => 45.0,
            'longitude' => -75.0,
            'altitude' => 420.0,
            'velocity' => 27000.0,
            'visibility' => 'daylight',
            'footprint' => 4500.0,
            'timestamp' => 1735689600,
            'daynum' => 2460676.5,
            'solar_lat' => 0.0,
            'solar_lon' => 0.0,
            'units' => 'kilometers',
        ], JSON_THROW_ON_ERROR), ['http_code' => 200]));
    }
}

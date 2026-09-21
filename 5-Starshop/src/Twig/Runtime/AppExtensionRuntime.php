<?php

namespace App\Twig\Runtime;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Extension\RuntimeExtensionInterface;

// Twig runtime extension that provides methods 
// for retrieving ISS  (International Space Station) location data 
// and formatting time differences.
class AppExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        // Inject the HttpClientInterface and CacheInterface services
        private readonly HttpClientInterface $client,
        // Inject the cache service for storing ISS location data
        #[Autowire(service: 'iss_location_pool')]
        // Inject the cache service for storing ISS location data
        private readonly CacheInterface $issLocationPool,
    ) {
    }

    public function getIssLocationData(): ?array
    {
        try {
            return $this->issLocationPool->get('iss_location_data', function (ItemInterface $item): array {
                return $this->client->request('GET', 'https://api.wheretheiss.at/v1/satellites/25544')->toArray();
            });
        } catch (HttpClientExceptionInterface) {
            return null;
        }
    }

    public function ago(?\DateTimeInterface $date): string
    {
        if (!$date) {
            return 'Not yet arrived';
        }

        $now = new \DateTimeImmutable();
        $diff = $now->getTimestamp() - $date->getTimestamp();
        $future = $diff < 0;
        $diff = abs($diff);

        $units = [
            'year' => 31536000,
            'month' => 2592000,
            'week' => 604800,
            'day' => 86400,
            'hour' => 3600,
            'minute' => 60,
            'second' => 1,
        ];

        foreach ($units as $unit => $seconds) {
            $count = (int) floor($diff / $seconds);
            if ($count >= 1) {
                $label = sprintf('%d %s%s', $count, $unit, $count > 1 ? 's' : '');

                return $future ? sprintf('in %s', $label) : sprintf('%s ago', $label);
            }
        }

        return 'just now';
    }
}

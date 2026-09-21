<?php

namespace App\Twig;

use App\Twig\Runtime\AppExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('ago', [AppExtensionRuntime::class, 'ago']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_iss_location_data', [AppExtensionRuntime::class, 'getIssLocationData']),
        ];
    }
}

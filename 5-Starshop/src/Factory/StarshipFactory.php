<?php

namespace App\Factory;

use App\Entity\Starship;
use App\Model\StarshipStatusEnum;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Starship>
 */
final class StarshipFactory extends PersistentObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return Starship::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'captain' => self::faker()->randomElement(self::CAPTAINS),
            'class' => self::faker()->randomElement(self::CLASSES),
            'name' => self::faker()->randomElement(self::SHIP_NAMES),
            'status' => self::faker()->randomElement(StarshipStatusEnum::cases()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Starship $starship): void {})
        ;
    }

    private const SHIP_NAMES = [
        'Nebula Drifter',
        'Quantum Voyager',
        'Starlight Nomad',
    ];

    private const CLASSES = [
        'Eclipse',
        'Vanguard',
        'Specter',
    ];

    private const CAPTAINS = [
        'Orion Stark',
        'Lyra Voss',
        'Cassian Drake',
    ];
}

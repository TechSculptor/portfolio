<?php

namespace App\DataFixtures;

use App\Entity\Starship;
use App\Factory\DroidFactory;
use App\Factory\StarshipFactory;
use App\Factory\StarshipPartFactory;
use App\Model\StarshipStatusEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

// The AppFixtures class is responsible
// for loading initial data into the database.
class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Create 20 random starships using the StarshipFactory
        StarshipFactory::createMany(20);

        // Create specific starships with predefined attributes
        $ship1 = new Starship();
        $ship1->setName('USS LeafyCruiser (NCC-0001)');
        $ship1->setClass('Garden');
        $ship1->setCaptain('Jean-Luc Pickles');
        $ship1->setStatus(StarshipStatusEnum::IN_PROGRESS);
        $manager->persist($ship1);

        // Create another specific starship with predefined attributes
        $ship2 = new Starship();
        $ship2->setName('USS Espresso (NCC-1234-C)');
        $ship2->setClass('Latte');
        $ship2->setCaptain('James T. Quick!');
        $ship2->setStatus(StarshipStatusEnum::COMPLETED);
        $manager->persist($ship2);

        // Create a third specific starship with predefined attributes
        $ship3 = new Starship();
        $ship3->setName('USS Wanderlust (NCC-2024-W)');
        $ship3->setClass('Delta Tourist');
        $ship3->setCaptain('Kathryn Journeyway');
        $ship3->setStatus(StarshipStatusEnum::WAITING);
        $manager->persist($ship3);

        // Flush the changes to the database
        $manager->flush();

        // Create specific starships using the StarshipFactory
        // with predefined attributes
        StarshipFactory::createOne([
            'name' => 'USS LeafyCruiser (NCC-0001)',
            'class' => 'Garden',
            'captain' => 'Jean-Luc Pickles',
            'status' => StarshipStatusEnum::IN_PROGRESS,
        ]);

        // Create another specific starship using the StarshipFactory
        StarshipFactory::createOne([
            'name' => 'USS Wanderlust (NCC-2024-W)',
            'class' => 'Delta Tourist',
            'captain' => 'Kathryn Journeyway',
            'status' => StarshipStatusEnum::WAITING,
        ]);

        // Create 100 random droids using the DroidFactory
        DroidFactory::createMany(100);

        // Create 100 random starships using the StarshipFactory
        // and associate them with a random number of droids
        StarshipFactory::createMany(100, fn () => [
            'droids' => DroidFactory::randomRange(1, 5),
        ]);

        // Create 100 random starship parts
        // using the StarshipPartFactory
        StarshipPartFactory::createMany(100);
    }
}

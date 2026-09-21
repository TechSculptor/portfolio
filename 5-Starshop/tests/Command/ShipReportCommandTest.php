<?php

namespace App\Tests\Command;

use App\Factory\StarshipFactory;
use App\Factory\StarshipPartFactory;
use App\Model\StarshipStatusEnum;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ShipReportCommandTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testReportListsShipsWithPartsAndDroids(): void
    {
        self::bootKernel();
        $ship = StarshipFactory::createOne(['name' => 'USS Testable', 'status' => StarshipStatusEnum::WAITING]);
        StarshipPartFactory::createOne(['starship' => $ship, 'price' => 1000]);

        $application = new Application(self::$kernel);
        $tester = new CommandTester($application->find('app:ship-report'));
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('USS Testable', $tester->getDisplay());
        self::assertStringContainsString('1 000 credits', $tester->getDisplay());
    }

    public function testUnknownStatusFails(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $tester = new CommandTester($application->find('app:ship-report'));
        $tester->execute(['--status' => 'not-a-real-status']);

        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('Unknown status', $tester->getDisplay());
    }
}

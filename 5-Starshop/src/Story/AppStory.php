<?php

namespace App\Story;

use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;

// code that lets us create fixtures for the application,
// using Zenstruck Foundry
#[AsFixture(name: 'main')]
final class AppStory extends Story
{
    public function build(): void
    {
        // SomeFactory::createOne();
    }
}

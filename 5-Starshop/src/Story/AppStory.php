<?php

namespace App\Story;

use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;


// code qui permet de créer des fixtures pour l'application, 
// en utilisant Zenstruck Foundry
#[AsFixture(name: 'main')]
final class AppStory extends Story
{
    public function build(): void
    {
        // SomeFactory::createOne();
    }
}

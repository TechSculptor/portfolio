<?php

namespace App\Model;

class Starship
{
    public function __construct(
        private \DateTimeImmutable $arrivedAt,
    ) {
    }

    public function getArrivedAt(): \DateTimeImmutable
    {
        return $this->arrivedAt;
    }
}

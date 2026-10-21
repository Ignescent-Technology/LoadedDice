<?php

namespace Ignescent\LoadedDice\Distributions\Discrete;

use Random\Randomizer;

final class Geometric implements Distribution
{
    public function __construct(protected Randomizer $randomizer, public float $p)
    {
    }

    public function random(): int
    {
        $result = 0;

        while ($this->randomizer->getFloat(0.0, 1.0) >= $this->p) {
            $result++;
        }

        return $result;
    }
}
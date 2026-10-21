<?php

namespace Ignescent\LoadedDice\Distributions\Continuous;

use Random\Randomizer;

final class Exponential implements Distribution
{
    public function __construct(
        protected Randomizer $randomizer,
        public float $lambda,
    )
    {
    }

    public function random(): float
    {
        return -log($this->randomizer->getFloat(0.0, 1.0)) / $this->lambda;
    }
}
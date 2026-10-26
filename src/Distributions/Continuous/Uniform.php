<?php

namespace Ignescent\LoadedDice\Distributions\Continuous;

use Random\Randomizer;

final class Uniform implements Distribution
{
    public function __construct(
        protected Randomizer $randomizer,
        public float $min = 0.0,
        public float $max = 1.0,
    )
    {
    }

    public function random(): float
    {
        return $this->randomizer->getFloat($this->min, $this->max);
    }
}

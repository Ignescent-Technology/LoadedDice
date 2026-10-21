<?php

namespace Ignescent\LoadedDice\Distributions\Continuous;

use Random\Randomizer;

final class Uniform implements Distribution
{
    public function __construct(
        protected Randomizer $randomizer,
        public float $min,
        public float $max
    )
    {
    }

    public function random(): float
    {
        return $this->randomizer->getFloat($this->min, $this->max);
    }
}

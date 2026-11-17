<?php

namespace Ignescent\LoadedDice\Distributions\Discrete;

use Random\Randomizer;

final class Uniform implements Distribution
{
    public function __construct(
        public int $min,
        public int $max,
        protected Randomizer $randomizer = new Randomizer(),
    )
    {
    }

    public function random(): int
    {
        return $this->randomizer->getInt($this->min, $this->max);
    }

    public function cdf(int $x): float
    {
        if ($x < $this->min) {
            return 0.0;
        }

        if ($x >= $this->max) {
            return 1.0;
        }

        return ($x - $this->min + 1) / ($this->max - $this->min + 1);
    }

    public function pmf(int $x): float
    {
        if ($x < $this->min || $x > $this->max) {
            return 0.0;
        }

        return 1.0 / ($this->max - $this->min + 1);
    }
}

<?php

namespace Ignescent\LoadedDice\Distributions\Continuous;

use Random\Randomizer;

/**
 * Continuous Uniform distribution over the real interval [min, max].
 */
final class Uniform implements Distribution
{
    public function __construct(
        public float $min = 0.0,
        public float $max = 1.0,
        protected Randomizer $randomizer = new Randomizer(),
    )
    {
    }

    public function random(): float
    {
        return $this->randomizer->getFloat($this->min, $this->max);
    }

    public function cdf(float $x): float
    {
        return ($x - $this->min) / ($this->max - $this->min);
    }

    public function pdf(float $x): float
    {
        return 1 / ($this->max - $this->min);
    }
}

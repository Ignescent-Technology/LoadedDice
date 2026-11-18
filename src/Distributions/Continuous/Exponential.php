<?php

namespace Ignescent\LoadedDice\Distributions\Continuous;

use Random\Randomizer;

/**
 * Continuous Exponential distribution parameterized by rate parameter lambda.
 */
final class Exponential implements Distribution
{
    public function __construct(
        public float $lambda,
        protected Randomizer $randomizer = new Randomizer(),
    )
    {
    }

    public function random(): float
    {
        return -log($this->randomizer->getFloat(0.0, 1.0)) / $this->lambda;
    }

    public function cdf(float $x): float
    {
        return 1 - exp(-$this->lambda * $x);
    }

    public function pdf(float $x): float
    {
        return $this->lambda * exp(-$this->lambda * $x);
    }
}
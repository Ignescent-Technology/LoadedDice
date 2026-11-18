<?php

namespace Ignescent\LoadedDice\Distributions\Discrete;

use Random\Randomizer;

/**
 * Discrete Geometric distribution representing the number of failures before the first success (0-indexed).
 */
final class Geometric implements Distribution
{
    public function __construct(
        public float $p,
        protected Randomizer $randomizer = new Randomizer(),
    )
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

    public function cdf(int $x): float
    {
        if ($x < 0) {
            return 0.0;
        }

        if ($this->p <= 0.0) {
            return 0.0;
        }

        if ($this->p === 1.0) {
            return 1.0;
        }

        return max(0.0, min(1.0, 1.0 - ((1.0 - $this->p) ** ($x + 1))));
    }

    public function pmf(int $x): float
    {
        if ($x < 0) {
            return 0.0;
        }

        if ($this->p <= 0.0) {
            return 0.0;
        }

        if ($this->p === 1.0) {
            return $x === 0 ? 1.0 : 0.0;
        }

        return exp($x * log(1.0 - $this->p) + log($this->p));
    }
}
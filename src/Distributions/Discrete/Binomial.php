<?php

namespace Ignescent\LoadedDice\Distributions\Discrete;

use Random\Randomizer;

final class Binomial implements Distribution
{
    public function __construct(
        protected Randomizer $randomizer,
        public int $n,
        public float $p,
    )
    {
    }

    public function random(): int
    {
        $result = 0;

        for ($i = 0; $i < $this->n; $i++) {
            if ($this->randomizer->getFloat(0.0, 1.0) < $this->p) {
                $result++;
            }
        }

        return $result;
    }
}

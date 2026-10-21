<?php

namespace Ignescent\LoadedDice\Distributions\Discrete;

use Random\Randomizer;

final class Poisson implements Distribution
{
    private float $step;

    public function __construct(
        protected Randomizer $randomizer,
        public float $lambda
    )
    {
        $this->step = 500.0;
    }

    public function random(): int
    {
        $left = $this->lambda;
        $k = 0;
        $p = 1.0;

        do {
            $k++;
            $p *= $this->randomizer->getFloat(0, 1);

            while ($p < 1.0 and $left > 0.0) {
                if ($left > $this->step) {
                    $p *= exp($this->step);
                    $left -= $this->step;
                } else {
                    $p *= exp($left);
                    $left = 0.0;
                }
            }
        } while ($p > 1.0);

        return $k - 1;
    }
}

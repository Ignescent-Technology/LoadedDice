<?php

namespace Ignescent\LoadedDice\Distributions\Discrete;

use Ignescent\LoadedDice\Utils;
use Random\Randomizer;

final class Poisson implements Distribution
{
    private float $step;

    public function __construct(
        public float $lambda,
        protected Randomizer $randomizer = new Randomizer(),
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

    public function cdf(int $x): float
    {
        if ($x < 0) {
            return 0.0;
        }

        if ($this->lambda <= 0.0) {
            return 1.0;
        }

        return max(0.0, min(1.0, 1.0 - Utils::regularizedGamma($x + 1, $this->lambda)));
    }

    public function pmf(int $x): float
    {
        if ($x < 0) {
            return 0.0;
        }

        if ($this->lambda <= 0.0) {
            return $x === 0 ? 1.0 : 0.0;
        }

        $logPmf = $x * log($this->lambda) - $this->lambda - Utils::logGamma($x + 1);

        return exp($logPmf);
    }
}

<?php

namespace Ignescent\LoadedDice\Distributions\Discrete;

use Ignescent\LoadedDice\Utils;
use Random\Randomizer;

final class Binomial implements Distribution
{
    public function __construct(
        public int $n,
        public float $p,
        protected Randomizer $randomizer = new Randomizer(),
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

    public function cdf(int $x): float
    {
        if ($x < 0) {
            return 0.0;
        }

        if ($x >= $this->n) {
            return 1.0;
        }

        if ($this->p === 0.0) {
            return 1.0;
        }

        if ($this->p === 1.0) {
            return 0.0;
        }

        return max(0.0, min(1.0, Utils::regularizedIncompleteBeta($this->n - $x, $x + 1, 1.0 - $this->p)));
    }

    public function pmf(int $x): float
    {
        if ($x < 0 || $x > $this->n) {
            return 0.0;
        }

        if ($this->n === 0) {
            return $x === 0 ? 1.0 : 0.0;
        }

        if ($this->p === 0.0) {
            return $x === 0 ? 1.0 : 0.0;
        }

        if ($this->p === 1.0) {
            return $x === $this->n ? 1.0 : 0.0;
        }

        $logBinomialCoeff = Utils::logGamma($this->n + 1) - Utils::logGamma($x + 1) - Utils::logGamma($this->n - $x + 1);
        $logPmf = $logBinomialCoeff + $x * log($this->p) + ($this->n - $x) * log(1.0 - $this->p);

        return exp($logPmf);
    }
}

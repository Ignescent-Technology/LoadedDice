<?php

namespace Ignescent\LoadedDice\Distributions\Continuous;

use Ignescent\LoadedDice\Utils;
use Random\Randomizer;

final class Beta implements Distribution
{
    private Gamma $alphaGamma;
    private Gamma $betaGamma;

    public function __construct(
        protected Randomizer $randomizer,
        public float $alpha,
        public float $beta,
    )
    {
        $this->alphaGamma = new Gamma($this->randomizer, $this->alpha, 1.0);
        $this->betaGamma = new Gamma($this->randomizer, $this->beta, 1.0);
    }

    public function random(): float
    {
        $x = $this->alphaGamma->random();
        $y = $this->betaGamma->random();

        return $x / ($x + $y);
    }

    public function cdf(float $x): float
    {
        if ($x <= 0.0) {
            return 0.0;
        }

        if ($x >= 1.0) {
            return 1.0;
        }

        return Utils::regularizedIncompleteBeta($this->alpha, $this->beta, $x);
    }

    public function pdf(float $x): float
    {
        if ($x < 0.0 || $x > 1.0) {
            return 0.0;
        }

        if ($x === 0.0) {
            if ($this->alpha < 1.0) {
                return INF;
            }

            if ($this->alpha === 1.0) {
                return $this->beta;
            }

            return 0.0;
        }

        if ($x === 1.0) {
            if ($this->beta < 1.0) {
                return INF;
            }

            if ($this->beta === 1.0) {
                return $this->alpha;
            }

            return 0.0;
        }

        $beta = Utils::beta($this->alpha, $this->beta);

        return (pow($x, $this->alpha - 1.0) * pow(1.0 - $x, $this->beta - 1.0)) / $beta;
    }
}
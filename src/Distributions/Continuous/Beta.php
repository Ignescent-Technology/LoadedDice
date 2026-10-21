<?php

namespace Ignescent\LoadedDice\Distributions\Continuous;

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
}
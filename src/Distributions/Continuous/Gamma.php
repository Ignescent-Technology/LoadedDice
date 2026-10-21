<?php

namespace Ignescent\LoadedDice\Distributions\Continuous;

use Random\Randomizer;

final class Gamma implements Distribution
{
    private Uniform $uniform;
    private Normal $normal;

    public function __construct(
        protected Randomizer $randomizer,
        public float $shape,
        public float $scale,
    )
    {
        $this->uniform = new Uniform($this->randomizer, 0.0, 1.0);
        $this->normal = new Normal($this->randomizer);
    }

    public function random(): float
    {
        $d = $this->shape - 1.0 / 3.0;
        $c = 1.0 / sqrt(9.0 * $d);

        do {
            $normalVariate = $this->normal->random();
            $uniformVariate = $this->uniform->random();

            $v = pow(1 + $c * $normalVariate, 3);
        } while ($v <= 0.0 || log($uniformVariate) >= pow($normalVariate, 2) / 2.0 + $d - $d * $v + $d * log($v));

        return $d * $v;
    }
}
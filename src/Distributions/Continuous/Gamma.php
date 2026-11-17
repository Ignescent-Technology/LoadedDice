<?php

namespace Ignescent\LoadedDice\Distributions\Continuous;

use Ignescent\LoadedDice\Utils;
use Random\Randomizer;

final class Gamma implements Distribution
{
    private Uniform $uniform;
    private Normal $normal;

    public function __construct(
        public float $shape,
        public float $scale,
        protected Randomizer $randomizer = new Randomizer(),
    )
    {
        $this->uniform = new Uniform(0.0, 1.0, $this->randomizer);
        $this->normal = new Normal(0.0, 1.0, $this->randomizer);
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

    public function cdf(float $x): float
    {
        if ($x <= 0.0) {
            return 0.0;
        }

        return Utils::regularizedGamma($this->shape, $x / $this->scale);
    }

    public function pdf(float $x): float
    {
        if ($x < 0.0) {
            return 0.0;
        }

        if ($x === 0.0) {
            if ($this->shape < 1.0) {
                return INF;
            }

            if ($this->shape === 1.0) {
                return 1.0 / $this->scale;
            }

            return 0.0;
        }

        $logPdf = ($this->shape - 1.0) * log($x) - ($x / $this->scale) - $this->shape * log($this->scale) - Utils::logGamma($this->shape);

        return exp($logPdf);
    }
}
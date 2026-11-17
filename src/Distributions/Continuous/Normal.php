<?php

namespace Ignescent\LoadedDice\Distributions\Continuous;

use Ignescent\LoadedDice\Utils;
use Random\Randomizer;

final class Normal implements Distribution
{
    private array $standardVariates = [];

    private Uniform $uniform;

    private float $variance;

    public function __construct(
        protected Randomizer $randomizer,
        public float $mean = 0.0,
        public float $standardDeviation = 1.0,
    )
    {
        $this->uniform = new Uniform($this->randomizer, -1.0, 1.0);
        $this->variance = pow($this->standardDeviation, 2);
    }

    public function random(): float
    {
        if (empty($this->standardVariates)) {
            $this->generateStandardVariates();
        }

        $standardNormalVariate = array_pop($this->standardVariates);

        return $this->mean + $this->standardDeviation * $standardNormalVariate;
    }

    private function generateStandardVariates(): void
    {
        do {
            $x = $this->uniform->random();
            $y = $this->uniform->random();
            $s = pow($x, 2) + pow($y, 2);
        } while ($s >= 1.0);

        $this->standardVariates[] = $x * sqrt(-2.0 * log($s) / $s);
        $this->standardVariates[] = $y * sqrt(-2.0 * log($s) / $s);
    }

    public function cdf(float $x): float
    {
        return 0.5 * (1 + Utils::erf(($x - $this->mean) / ($this->standardDeviation * M_SQRT2)));
    }

    public function pdf(float $x): float
    {
        return (1 / (sqrt(2 * M_PI * $this->variance))) * exp(-pow($x - $this->mean, 2) / (2 * $this->variance));
    }
}

<?php

namespace Ignescent\LoadedDice\Distributions\Continuous;

use Random\Randomizer;

final class Normal implements Distribution
{
    private array $standardVariates = [];

    private Uniform $uniform;

    public function __construct(
        protected Randomizer $randomizer,
        public float $mean = 0.0,
        public float $standardDeviation = 1.0,
    )
    {
        $this->uniform = new Uniform($this->randomizer, -1.0, 1.0);
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
}

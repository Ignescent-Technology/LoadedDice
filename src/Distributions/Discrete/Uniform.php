<?php

namespace Ignescent\LoadedDice\Distributions\Discrete;

use Random\Randomizer;

final class Uniform implements Distribution
{
    public function __construct(
        protected Randomizer $randomizer,
        public int $min,
        public int $max
    )
    {
    }

    public function random(): int
    {
        return $this->randomizer->getInt($this->min, $this->max);
    }
}

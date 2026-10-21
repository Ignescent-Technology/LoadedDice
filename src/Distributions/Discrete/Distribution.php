<?php

namespace Ignescent\LoadedDice\Distributions\Discrete;

use Ignescent\LoadedDice\Distributions\Distribution as BaseDistribution;
use Random\Randomizer;

interface Distribution extends BaseDistribution
{
    public function random(): int;
}

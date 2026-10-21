<?php

namespace Ignescent\LoadedDice\Distributions\Continuous;

use Ignescent\LoadedDice\Distributions\Distribution as BaseDistribution;

interface Distribution extends BaseDistribution
{
    public function random(): float;
}

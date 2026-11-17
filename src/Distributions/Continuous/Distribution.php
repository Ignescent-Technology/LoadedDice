<?php

namespace Ignescent\LoadedDice\Distributions\Continuous;

use Ignescent\LoadedDice\Distributions\Distribution as BaseDistribution;

interface Distribution extends BaseDistribution
{
    public function cdf(float $x): float;

    public function pdf(float $x): float;

    public function random(): float;
}

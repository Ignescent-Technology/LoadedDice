<?php

namespace Ignescent\LoadedDice\Distributions\Discrete;

use Ignescent\LoadedDice\Distributions\Distribution as BaseDistribution;
use Random\Randomizer;

/**
 * Contract for discrete probability distributions supporting variate generation,
 * cumulative distribution (CDF), and probability mass (PMF) functions.
 */
interface Distribution extends BaseDistribution
{
    public function cdf(int $x): float;

    public function pmf(int $x): float;

    public function random(): int;
}

<?php

namespace Ignescent\LoadedDice\Distributions\Continuous;

use Ignescent\LoadedDice\Distributions\Distribution as BaseDistribution;

/**
 * Contract for continuous probability distributions supporting variate generation,
 * cumulative distribution (CDF), and probability density (PDF) functions.
 */
interface Distribution extends BaseDistribution
{
    public function cdf(float $x): float;

    public function pdf(float $x): float;

    public function random(): float;
}

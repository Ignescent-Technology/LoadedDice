<?php

namespace Ignescent\LoadedDice\Distributions;

/**
 * Base contract for statistical probability distributions.
 */
interface Distribution
{
    public function random(): float|int;
}

<?php

namespace Ignescent\LoadedDice\WeightResolving;

use Stringable;

/**
 * How to resolve weights for elements in a weighted rendezvous hashing scheme
 */
interface WeightResolver
{
    /**
     * @param string|Stringable $element String representation of the element to resolve the weight for.
     * @return float|int The weight of the passed element, as a positive number.
     */
    public function resolveWeight(string|Stringable $element): float|int;
}

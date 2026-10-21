<?php

namespace Ignescent\LoadedDice\WeightResolving;

use Stringable;

final class MapResolver implements WeightResolver
{
    /**
     * @param array<string, float|int> $weights A map of element to weight.
     * @param float|int $defaultWeight The default weight to use for elements not found in the map, defaulting to 1.0.
     */
    public function __construct(
        private readonly array $weights,
        private readonly float|int $defaultWeight = 1.0,
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function resolveWeight(string|Stringable $element): float|int
    {
        return $this->weights[(string) $element] ?? $this->defaultWeight;
    }
}

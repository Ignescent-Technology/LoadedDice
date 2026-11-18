<?php

namespace Ignescent\LoadedDice\Rendezvous;

use Ignescent\LoadedDice\Hashing\Hash;
use Ignescent\LoadedDice\Utils;
use Ignescent\LoadedDice\WeightResolving\WeightResolver;
use Stringable;

/**
 * Weighted rendezvous (Highest Random Weight) consistent hashing distributing keys
 * proportional to node capacity weights.
 */
final class Weighted extends Base
{
    public function __construct(
        private readonly Hash $hash,
        private readonly WeightResolver $weightResolver,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    protected function elementPriority(string|Stringable $element): float|int
    {
        $score = Utils::hashToFloat($this->hash->hash((string) $element));
        $logScore = 1.0 / log($score); // Invert since we're using a min-heap.

        return $logScore * $this->weightResolver->resolveWeight($element);
    }
}

<?php

namespace Ignescent\LoadedDice;

use Ignescent\LoadedDice\Hashing\Hash;
use MathieuViossat\Util\FibonacciHeap;
use Stringable;

final readonly class RandomOracle
{
    public function __construct(
        private Hash $hash
    ) {
    }


    /**
     * Performs a coin toss using the supplied value.
     *
     * @param string|Stringable $value
     * @param float $probability Default 50%
     * @return bool
     */
    public function boolean(string|Stringable $value, float $probability = 0.5): bool
    {
        return $this->float($value) < $probability;
    }

    /**
     * Returns a random integer using the supplied value in the range [0, $max).
     *
     * @param string|Stringable $value
     * @param int $max
     * @return int
     */
    public function integer(string|Stringable $value, int $max = PHP_INT_MAX): int
    {
        return floor($this->float($value) / $max);
    }

    /**
     * Returns a value in the range [0, 1) using the supplied value.
     *
     * @param string|Stringable $value
     * @return float
     */
    public function float(string|Stringable $value): float
    {
        return Utils::hashToFloat($this->hash->hash((string) $value));
    }
}
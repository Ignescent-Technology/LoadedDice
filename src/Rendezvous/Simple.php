<?php

namespace Ignescent\LoadedDice\Rendezvous;

use Ignescent\LoadedDice\Hashing\Hash;
use Stringable;

/**
 * Uniform rendezvous (Highest Random Weight) consistent hashing over identical nodes.
 */
final class Simple extends Base
{
    public function __construct(
        private readonly Hash $hash
    ) {
    }

    /**
     * {@inheritdoc}
     */
    protected function elementPriority(string|Stringable $element): float|int
    {
        return hexdec($this->hash->hash((string) $element));
    }
}

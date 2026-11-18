<?php

namespace Ignescent\LoadedDice\Hashing;

use Random\Engine;

/**
 * Contract for deterministic hashing and pseudo-random engine seeding.
 */
interface Hash
{
    public function hash(string $value) : string;

    public function seedEngine(string $value): Engine;
}

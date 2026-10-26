<?php

namespace Ignescent\LoadedDice\Hashing;

use Random\Engine;

interface Hash
{
    public function hash(string $value) : string;

    public function seedEngine(string $value): Engine;
}

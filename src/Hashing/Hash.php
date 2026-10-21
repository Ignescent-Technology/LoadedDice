<?php

namespace Ignescent\LoadedDice\Hashing;

interface Hash
{
    public function hash(string $value) : string;
}

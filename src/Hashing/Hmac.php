<?php

namespace Ignescent\LoadedDice\Hashing;

use Random\Engine;
use Random\Engine\Xoshiro256StarStar;

final readonly class Hmac implements Hash
{
    public function __construct(
        private string $key,
        private string $algo = "sha256",
    )
    {
    }

    public function hash(string $value): string
    {
        return hash_hmac($this->algo, $value, $this->key);
    }

    public function seedEngine(string $value): Engine
    {
        $hash = hash_hmac($this->algo, $value, $this->key, true);

        if (strlen($hash) !== 32) {
            $hash = hash('sha256', $hash, true);
        }

        return new Xoshiro256StarStar($hash);
    }
}

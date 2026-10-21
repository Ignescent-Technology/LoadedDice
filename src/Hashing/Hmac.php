<?php

namespace Ignescent\LoadedDice\Hashing;

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
}

<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests\Hashing;

use Ignescent\LoadedDice\Hashing\Hash;
use Ignescent\LoadedDice\Hashing\Hmac;
use PHPUnit\Framework\TestCase;

class HmacTest extends TestCase
{
    public function testImplementsHashInterface(): void
    {
        $hmac = new Hmac('secret-key');

        $this->assertInstanceOf(Hash::class, $hmac);
    }

    public function testDefaultAlgorithmIsSha256(): void
    {
        $key = 'secret-key';
        $value = 'hello-world';
        $expected = hash_hmac('sha256', $value, $key);

        $hmac = new Hmac($key);

        $this->assertSame($expected, $hmac->hash($value));
    }

    public function testCustomAlgorithm(): void
    {
        $key = 'secret-key';
        $value = 'hello-world';

        $hmacSha512 = new Hmac($key, 'sha512');
        $this->assertSame(hash_hmac('sha512', $value, $key), $hmacSha512->hash($value));

        $hmacMd5 = new Hmac($key, 'md5');
        $this->assertSame(hash_hmac('md5', $value, $key), $hmacMd5->hash($value));
    }

    public function testDeterministicHash(): void
    {
        $hmac1 = new Hmac('deterministic-key');
        $hmac2 = new Hmac('deterministic-key');

        $this->assertSame($hmac1->hash('same-input'), $hmac2->hash('same-input'));
    }

    public function testDifferentKeysProduceDifferentHashes(): void
    {
        $hmac1 = new Hmac('key-1');
        $hmac2 = new Hmac('key-2');

        $this->assertNotSame($hmac1->hash('same-input'), $hmac2->hash('same-input'));
    }

    public function testDifferentInputsProduceDifferentHashes(): void
    {
        $hmac = new Hmac('secret-key');

        $this->assertNotSame($hmac->hash('input-1'), $hmac->hash('input-2'));
    }
}

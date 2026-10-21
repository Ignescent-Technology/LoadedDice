<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests;

use Ignescent\LoadedDice\Utils;
use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase
{
    public function testHashToFloatWithEmptyStringReturnsZero(): void
    {
        $this->assertSame(0.0, Utils::hashToFloat(''));
    }

    public function testHashToFloatWithSingleCharReturnsZero(): void
    {
        // 1 char has floor(1/2) = 0 bytes
        $this->assertSame(0.0, Utils::hashToFloat('a'));
    }

    public function testHashToFloatWithAllZeroHexReturnsZero(): void
    {
        $this->assertSame(0.0, Utils::hashToFloat('00000000'));
        $this->assertSame(0.0, Utils::hashToFloat('0000000000000000'));
    }

    public function testHashToFloatWithAllFHexReturnsOne(): void
    {
        $this->assertSame(1.0, Utils::hashToFloat('ffffffff'));
        $this->assertSame(1.0, Utils::hashToFloat('ffffffffffffffff'));
    }

    public function testHashToFloatReturnsDeterministicFloatInRange(): void
    {
        $hash = hash('sha256', 'example-input');
        $float1 = Utils::hashToFloat($hash);
        $float2 = Utils::hashToFloat($hash);

        $this->assertSame($float1, $float2);
        $this->assertGreaterThanOrEqual(0.0, $float1);
        $this->assertLessThanOrEqual(1.0, $float1);
    }

    public function testHashToFloatDifferentInputsProduceDifferentOutputs(): void
    {
        $hash1 = hash('sha256', 'input-1');
        $hash2 = hash('sha256', 'input-2');

        $this->assertNotSame(Utils::hashToFloat($hash1), Utils::hashToFloat($hash2));
    }

    public function testHashToFloatHandlesDifferentByteLengths(): void
    {
        // 2 hex chars = 1 byte (max 255)
        $this->assertSame(128 / 255, Utils::hashToFloat('80'));

        // 4 hex chars = 2 bytes (max 65535)
        $this->assertSame(0x8000 / 0xffff, Utils::hashToFloat('8000'));
    }
}

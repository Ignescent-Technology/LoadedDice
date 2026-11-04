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

    public function testGammaFactorialValuesForIntegers(): void
    {
        $this->assertEqualsWithDelta(1.0, Utils::gamma(1.0), 1e-10);
        $this->assertEqualsWithDelta(1.0, Utils::gamma(2.0), 1e-10);
        $this->assertEqualsWithDelta(2.0, Utils::gamma(3.0), 1e-10);
        $this->assertEqualsWithDelta(6.0, Utils::gamma(4.0), 1e-10);
        $this->assertEqualsWithDelta(24.0, Utils::gamma(5.0), 1e-10);
        $this->assertEqualsWithDelta(120.0, Utils::gamma(6.0), 1e-10);
    }

    public function testGammaHalfIntegerValues(): void
    {
        $this->assertEqualsWithDelta(sqrt(M_PI), Utils::gamma(0.5), 1e-10);
        $this->assertEqualsWithDelta(0.5 * sqrt(M_PI), Utils::gamma(1.5), 1e-10);
        $this->assertEqualsWithDelta(0.75 * sqrt(M_PI), Utils::gamma(2.5), 1e-10);
        $this->assertEqualsWithDelta(1.875 * sqrt(M_PI), Utils::gamma(3.5), 1e-10);
        $this->assertEqualsWithDelta(-2.0 * sqrt(M_PI), Utils::gamma(-0.5), 1e-10);
    }

    public function testGammaRecurrenceRelation(): void
    {
        $testValues = [0.7, 1.3, 2.8, 4.2, 5.5];

        foreach ($testValues as $x) {
            $expected = $x * Utils::gamma($x);
            $actual = Utils::gamma($x + 1.0);
            $this->assertEqualsWithDelta($expected, $actual, 1e-9);
        }
    }

    public function testGammaNonPositiveIntegersReturnInfinity(): void
    {
        $this->assertTrue(is_infinite(Utils::gamma(0.0)));
        $this->assertTrue(is_infinite(Utils::gamma(-1.0)));
        $this->assertTrue(is_infinite(Utils::gamma(-2.0)));
    }

    public function testBetaKnownValues(): void
    {
        $this->assertEqualsWithDelta(1.0, Utils::beta(1.0, 1.0), 1e-10);
        $this->assertEqualsWithDelta(0.5, Utils::beta(1.0, 2.0), 1e-10);
        $this->assertEqualsWithDelta(1.0 / 6.0, Utils::beta(2.0, 2.0), 1e-10);
        $this->assertEqualsWithDelta(1.0 / 60.0, Utils::beta(3.0, 4.0), 1e-10);
        $this->assertEqualsWithDelta(0.2, Utils::beta(1.0, 5.0), 1e-10);
    }

    public function testBetaSymmetry(): void
    {
        $pairs = [
            [1.0, 2.0],
            [2.5, 4.1],
            [0.5, 1.5],
            [3.2, 0.8],
            [5.0, 2.0],
        ];

        foreach ($pairs as [$a, $b]) {
            $this->assertEqualsWithDelta(Utils::beta($a, $b), Utils::beta($b, $a), 1e-10);
        }
    }

    public function testBetaHalfIntegerValues(): void
    {
        $this->assertEqualsWithDelta(M_PI, Utils::beta(0.5, 0.5), 1e-10);
        $this->assertEqualsWithDelta(2.0, Utils::beta(0.5, 1.0), 1e-10);
    }

    public function testBetaRecurrenceRelation(): void
    {
        $testPairs = [
            [1.2, 2.3],
            [0.7, 3.5],
            [2.8, 1.9],
            [4.1, 5.2],
        ];

        foreach ($testPairs as [$a, $b]) {
            $base = Utils::beta($a, $b);
            $expectedA = $base * ($a / ($a + $b));
            $actualA = Utils::beta($a + 1.0, $b);
            $this->assertEqualsWithDelta($expectedA, $actualA, 1e-9);

            $expectedB = $base * ($b / ($a + $b));
            $actualB = Utils::beta($a, $b + 1.0);
            $this->assertEqualsWithDelta($expectedB, $actualB, 1e-9);
        }
    }
}

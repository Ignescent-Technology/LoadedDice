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

    public function testErfZeroReturnsZero(): void
    {
        $this->assertSame(0.0, Utils::erf(0.0));
    }

    public function testErfKnownValues(): void
    {
        // Reference values of erf(x)
        $knownValues = [
            [0.1, 0.1124629160],
            [0.5, 0.5204998778],
            [1.0, 0.8427007929],
            [1.5, 0.9661051465],
            [2.0, 0.9953222650],
            [3.0, 0.9999779095],
        ];

        foreach ($knownValues as [$x, $expected]) {
            $this->assertEqualsWithDelta($expected, Utils::erf($x), 1.5e-7);
        }
    }

    public function testErfSymmetry(): void
    {
        $testValues = [0.1, 0.5, 0.8, 1.2, 2.0, 3.5];

        foreach ($testValues as $x) {
            $this->assertEqualsWithDelta(-Utils::erf($x), Utils::erf(-$x), 1e-15);
        }
    }

    public function testErfAsymptoticLimits(): void
    {
        $this->assertEqualsWithDelta(1.0, Utils::erf(10.0), 1e-7);
        $this->assertEqualsWithDelta(-1.0, Utils::erf(-10.0), 1e-7);
    }

    public function testIncompleteLowerGammaZeroReturnsZero(): void
    {
        $this->assertSame(0.0, Utils::incompleteLowerGamma(1.0, 0.0));
        $this->assertSame(0.0, Utils::incompleteLowerGamma(2.5, 0.0));
    }

    public function testIncompleteLowerGammaInvalidInputsReturnNan(): void
    {
        $this->assertTrue(is_nan(Utils::incompleteLowerGamma(0.0, 1.0)));
        $this->assertTrue(is_nan(Utils::incompleteLowerGamma(-1.0, 1.0)));
        $this->assertTrue(is_nan(Utils::incompleteLowerGamma(1.0, -0.5)));
    }

    public function testIncompleteLowerGammaIntegerValues(): void
    {
        // For s = 1: gamma(1, x) = 1 - exp(-x)
        $this->assertEqualsWithDelta(1.0 - exp(-1.0), Utils::incompleteLowerGamma(1.0, 1.0), 1e-12);
        $this->assertEqualsWithDelta(1.0 - exp(-2.5), Utils::incompleteLowerGamma(1.0, 2.5), 1e-12);
        $this->assertEqualsWithDelta(1.0 - exp(-5.0), Utils::incompleteLowerGamma(1.0, 5.0), 1e-12);

        // For s = 2: gamma(2, x) = 1 - exp(-x) * (1 + x)
        $this->assertEqualsWithDelta(1.0 - exp(-1.0) * (1.0 + 1.0), Utils::incompleteLowerGamma(2.0, 1.0), 1e-12);
        $this->assertEqualsWithDelta(1.0 - exp(-3.0) * (1.0 + 3.0), Utils::incompleteLowerGamma(2.0, 3.0), 1e-12);

        // For s = 3: gamma(3, x) = 2 * (1 - exp(-x) * (1 + x + x^2 / 2))
        $expectedS3X2 = 2.0 * (1.0 - exp(-2.0) * (1.0 + 2.0 + (2.0 ** 2) / 2.0));
        $this->assertEqualsWithDelta($expectedS3X2, Utils::incompleteLowerGamma(3.0, 2.0), 1e-12);
    }

    public function testIncompleteLowerGammaHalfIntegerValues(): void
    {
        // For s = 0.5: gamma(0.5, x) = sqrt(pi) * erf(sqrt(x))
        // Known high-precision reference values
        $this->assertEqualsWithDelta(1.493648265624854, Utils::incompleteLowerGamma(0.5, 1.0), 1e-12);
        $this->assertEqualsWithDelta(1.764162781524843, Utils::incompleteLowerGamma(0.5, 4.0), 1e-12);

        // Verification against Utils::erf within Hastings approximation bounds
        $this->assertEqualsWithDelta(sqrt(M_PI) * Utils::erf(1.0), Utils::incompleteLowerGamma(0.5, 1.0), 5e-7);
        $this->assertEqualsWithDelta(sqrt(M_PI) * Utils::erf(2.0), Utils::incompleteLowerGamma(0.5, 4.0), 5e-7);
    }

    public function testIncompleteLowerGammaRecurrenceRelation(): void
    {
        $testCases = [
            [0.5, 0.3],
            [1.5, 2.5],
            [2.3, 1.8],
            [3.5, 5.0],
            [4.0, 2.0],
            [5.0, 7.5],
        ];

        foreach ($testCases as [$s, $x]) {
            $valS = Utils::incompleteLowerGamma($s, $x);
            $valSPlus1 = Utils::incompleteLowerGamma($s + 1.0, $x);

            $expectedRhs = ($x ** $s) * exp(-$x);
            $actualLhs = $s * $valS - $valSPlus1;

            $this->assertEqualsWithDelta($expectedRhs, $actualLhs, 1e-9);
        }
    }

    public function testIncompleteLowerGammaAsymptoticLimit(): void
    {
        $this->assertEqualsWithDelta(Utils::gamma(1.0), Utils::incompleteLowerGamma(1.0, 50.0), 1e-12);
        $this->assertEqualsWithDelta(Utils::gamma(2.5), Utils::incompleteLowerGamma(2.5, 60.0), 1e-12);
        $this->assertEqualsWithDelta(Utils::gamma(4.0), Utils::incompleteLowerGamma(4.0, 70.0), 1e-12);
    }

    public function testIncompleteUpperGammaZeroReturnsFullGamma(): void
    {
        $this->assertEqualsWithDelta(Utils::gamma(1.0), Utils::incompleteUpperGamma(1.0, 0.0), 1e-12);
        $this->assertEqualsWithDelta(Utils::gamma(2.5), Utils::incompleteUpperGamma(2.5, 0.0), 1e-12);
        $this->assertEqualsWithDelta(Utils::gamma(4.0), Utils::incompleteUpperGamma(4.0, 0.0), 1e-12);
    }

    public function testIncompleteUpperGammaInvalidInputsReturnNan(): void
    {
        $this->assertTrue(is_nan(Utils::incompleteUpperGamma(0.0, 1.0)));
        $this->assertTrue(is_nan(Utils::incompleteUpperGamma(-1.0, 1.0)));
        $this->assertTrue(is_nan(Utils::incompleteUpperGamma(1.0, -0.5)));
    }

    public function testIncompleteUpperGammaIntegerValues(): void
    {
        // For s = 1: Gamma(1, x) = exp(-x)
        $this->assertEqualsWithDelta(exp(-1.0), Utils::incompleteUpperGamma(1.0, 1.0), 1e-12);
        $this->assertEqualsWithDelta(exp(-2.5), Utils::incompleteUpperGamma(1.0, 2.5), 1e-12);
        $this->assertEqualsWithDelta(exp(-5.0), Utils::incompleteUpperGamma(1.0, 5.0), 1e-12);

        // For s = 2: Gamma(2, x) = exp(-x) * (1 + x)
        $this->assertEqualsWithDelta(exp(-1.0) * (1.0 + 1.0), Utils::incompleteUpperGamma(2.0, 1.0), 1e-12);
        $this->assertEqualsWithDelta(exp(-3.0) * (1.0 + 3.0), Utils::incompleteUpperGamma(2.0, 3.0), 1e-12);

        // For s = 3: Gamma(3, x) = exp(-x) * (2 + 2x + x^2)
        $expectedS3X2 = exp(-2.0) * (2.0 + 2.0 * 2.0 + (2.0 ** 2));
        $this->assertEqualsWithDelta($expectedS3X2, Utils::incompleteUpperGamma(3.0, 2.0), 1e-12);
    }

    public function testIncompleteUpperGammaHalfIntegerValues(): void
    {
        // For s = 0.5: Gamma(0.5, x) = sqrt(pi) * erfc(sqrt(x))
        $expectedX1 = sqrt(M_PI) - 1.493648265624854;
        $expectedX4 = sqrt(M_PI) - 1.764162781524843;

        $this->assertEqualsWithDelta($expectedX1, Utils::incompleteUpperGamma(0.5, 1.0), 1e-12);
        $this->assertEqualsWithDelta($expectedX4, Utils::incompleteUpperGamma(0.5, 4.0), 1e-12);
    }

    public function testIncompleteUpperGammaSumRelation(): void
    {
        $testCases = [
            [0.5, 0.3],
            [0.5, 1.0],
            [0.5, 4.0],
            [1.0, 1.0],
            [1.5, 2.5],
            [2.3, 1.8],
            [4.0, 5.0],
            [3.5, 10.0],
            [5.0, 2.0],
            [10.0, 15.0],
        ];

        foreach ($testCases as [$s, $x]) {
            $lower = Utils::incompleteLowerGamma($s, $x);
            $upper = Utils::incompleteUpperGamma($s, $x);
            $total = Utils::gamma($s);

            $this->assertEqualsWithDelta($total, $lower + $upper, 1e-9);
        }
    }

    public function testIncompleteUpperGammaRecurrenceRelation(): void
    {
        $testCases = [
            [0.5, 0.3],
            [1.5, 2.5],
            [2.3, 1.8],
            [4.0, 5.0],
            [3.5, 10.0],
            [5.0, 2.0],
            [0.1, 0.05],
            [10.0, 15.0],
        ];

        foreach ($testCases as [$s, $x]) {
            $valS = Utils::incompleteUpperGamma($s, $x);
            $valSPlus1 = Utils::incompleteUpperGamma($s + 1.0, $x);

            $expectedSPlus1 = $s * $valS + ($x ** $s) * exp(-$x);

            $this->assertEqualsWithDelta($expectedSPlus1, $valSPlus1, 1e-9);
        }
    }

    public function testIncompleteUpperGammaAsymptoticLimit(): void
    {
        $this->assertEqualsWithDelta(0.0, Utils::incompleteUpperGamma(1.0, 50.0), 1e-12);
        $this->assertEqualsWithDelta(0.0, Utils::incompleteUpperGamma(2.5, 60.0), 1e-12);
        $this->assertEqualsWithDelta(0.0, Utils::incompleteUpperGamma(4.0, 70.0), 1e-12);
    }

    public function testRegularizedGammaZeroReturnsZero(): void
    {
        $this->assertSame(0.0, Utils::regularizedGamma(1.0, 0.0));
        $this->assertSame(0.0, Utils::regularizedGamma(2.5, 0.0));
        $this->assertSame(0.0, Utils::regularizedGamma(5.0, 0.0));
    }

    public function testRegularizedGammaInvalidInputsReturnNan(): void
    {
        $this->assertTrue(is_nan(Utils::regularizedGamma(0.0, 1.0)));
        $this->assertTrue(is_nan(Utils::regularizedGamma(-1.0, 1.0)));
        $this->assertTrue(is_nan(Utils::regularizedGamma(1.0, -0.5)));
    }

    public function testRegularizedGammaIntegerValues(): void
    {
        // For s = 1: P(1, x) = 1 - exp(-x)
        $this->assertEqualsWithDelta(1.0 - exp(-1.0), Utils::regularizedGamma(1.0, 1.0), 1e-12);
        $this->assertEqualsWithDelta(1.0 - exp(-2.5), Utils::regularizedGamma(1.0, 2.5), 1e-12);
        $this->assertEqualsWithDelta(1.0 - exp(-5.0), Utils::regularizedGamma(1.0, 5.0), 1e-12);

        // For s = 2: P(2, x) = 1 - exp(-x) * (1 + x)
        $this->assertEqualsWithDelta(1.0 - exp(-1.0) * (1.0 + 1.0), Utils::regularizedGamma(2.0, 1.0), 1e-12);
        $this->assertEqualsWithDelta(1.0 - exp(-3.0) * (1.0 + 3.0), Utils::regularizedGamma(2.0, 3.0), 1e-12);

        // For s = 3: P(3, x) = 1 - exp(-x) * (1 + x + x^2 / 2)
        $expectedS3X2 = 1.0 - exp(-2.0) * (1.0 + 2.0 + (2.0 ** 2) / 2.0);
        $this->assertEqualsWithDelta($expectedS3X2, Utils::regularizedGamma(3.0, 2.0), 1e-12);
    }

    public function testRegularizedGammaHalfIntegerValues(): void
    {
        // For s = 0.5: P(0.5, x) = erf(sqrt(x))
        $this->assertEqualsWithDelta(Utils::erf(1.0), Utils::regularizedGamma(0.5, 1.0), 5e-7);
        $this->assertEqualsWithDelta(Utils::erf(2.0), Utils::regularizedGamma(0.5, 4.0), 5e-7);
    }

    public function testRegularizedGammaConsistency(): void
    {
        $testCases = [
            [0.5, 0.3],
            [0.5, 1.0],
            [0.5, 4.0],
            [1.0, 1.0],
            [1.5, 2.5],
            [2.3, 1.8],
            [4.0, 5.0],
            [3.5, 10.0],
            [5.0, 2.0],
            [10.0, 15.0],
        ];

        foreach ($testCases as [$s, $x]) {
            $reg = Utils::regularizedGamma($s, $x);
            $lower = Utils::incompleteLowerGamma($s, $x);
            $upper = Utils::incompleteUpperGamma($s, $x);
            $gamma = Utils::gamma($s);

            $this->assertEqualsWithDelta($lower / $gamma, $reg, 1e-12);
            $this->assertEqualsWithDelta(1.0, $reg + ($upper / $gamma), 1e-12);
            $this->assertGreaterThanOrEqual(0.0, $reg);
            $this->assertLessThanOrEqual(1.0, $reg);
        }
    }

    public function testRegularizedGammaAsymptoticLimits(): void
    {
        $this->assertEqualsWithDelta(1.0, Utils::regularizedGamma(1.0, 50.0), 1e-12);
        $this->assertEqualsWithDelta(1.0, Utils::regularizedGamma(2.5, 60.0), 1e-12);
        $this->assertEqualsWithDelta(1.0, Utils::regularizedGamma(4.0, 70.0), 1e-12);
    }
}

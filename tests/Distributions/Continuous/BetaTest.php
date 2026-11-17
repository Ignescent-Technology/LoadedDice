<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests\Distributions\Continuous;

use Ignescent\LoadedDice\Distributions\Continuous\Beta;
use Ignescent\LoadedDice\Distributions\Continuous\Distribution as ContinuousDistribution;
use Ignescent\LoadedDice\Distributions\Distribution;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

class BetaTest extends TestCase
{
    public function testImplementsDistributionInterfaces(): void
    {
        $beta = new Beta(2.0, 5.0);

        $this->assertInstanceOf(Distribution::class, $beta);
        $this->assertInstanceOf(ContinuousDistribution::class, $beta);
    }

    public function testConstructorSetsProperties(): void
    {
        $beta = new Beta(2.0, 5.0);

        $this->assertSame(2.0, $beta->alpha);
        $this->assertSame(5.0, $beta->beta);
    }

    public function testConstructorDefaultRandomizer(): void
    {
        $beta = new Beta(2.0, 5.0);
        $val = $beta->random();

        $this->assertIsFloat($val);
        $this->assertGreaterThan(0.0, $val);
        $this->assertLessThan(1.0, $val);
    }

    public function testRandomReturnsFloatInOpenInterval(): void
    {
        $beta = new Beta(2.0, 5.0, new Randomizer(new Mt19937(42)));

        for ($i = 0; $i < 50; $i++) {
            $value = $beta->random();
            $this->assertIsFloat($value);
            $this->assertGreaterThan(0.0, $value);
            $this->assertLessThan(1.0, $value);
        }
    }

    public function testDeterministicWithSameSeed(): void
    {
        $beta1 = new Beta(2.0, 3.0, new Randomizer(new Mt19937(12345)));
        $beta2 = new Beta(2.0, 3.0, new Randomizer(new Mt19937(12345)));

        for ($i = 0; $i < 20; $i++) {
            $this->assertSame($beta1->random(), $beta2->random());
        }
    }

    public function testDistributionMeanApproximation(): void
    {
        $alpha = 2.0;
        $betaParam = 5.0;
        $beta = new Beta($alpha, $betaParam, new Randomizer(new Mt19937(42)));
        $samples = 5000;
        $sum = 0.0;

        for ($i = 0; $i < $samples; $i++) {
            $sum += $beta->random();
        }

        $mean = $sum / $samples;
        $expectedMean = $alpha / ($alpha + $betaParam); // 2/7 ≈ 0.2857
        $this->assertEqualsWithDelta($expectedMean, $mean, 0.05);
    }

    public function testCdfBoundaryConditions(): void
    {
        $beta = new Beta(2.0, 5.0);

        $this->assertSame(0.0, $beta->cdf(-1.0));
        $this->assertSame(0.0, $beta->cdf(-0.01));
        $this->assertSame(0.0, $beta->cdf(0.0));
        $this->assertSame(1.0, $beta->cdf(1.0));
        $this->assertSame(1.0, $beta->cdf(1.01));
        $this->assertSame(1.0, $beta->cdf(5.0));
    }

    public function testCdfUniformSpecialCase(): void
    {
        $beta = new Beta(1.0, 1.0);

        foreach ([0.1, 0.25, 0.5, 0.75, 0.9] as $x) {
            $this->assertEqualsWithDelta($x, $beta->cdf($x), 1e-12);
        }
    }

    public function testCdfPowerSpecialCases(): void
    {
        // For Beta(1, beta): CDF(x) = 1 - (1 - x)^beta
        $beta1 = new Beta(1.0, 3.0);
        foreach ([0.1, 0.25, 0.5, 0.75, 0.9] as $x) {
            $expected = 1.0 - ((1.0 - $x) ** 3.0);
            $this->assertEqualsWithDelta($expected, $beta1->cdf($x), 1e-12);
        }

        // For Beta(alpha, 1): CDF(x) = x^alpha
        $beta2 = new Beta(4.0, 1.0);
        foreach ([0.1, 0.25, 0.5, 0.75, 0.9] as $x) {
            $expected = $x ** 4.0;
            $this->assertEqualsWithDelta($expected, $beta2->cdf($x), 1e-12);
        }
    }

    public function testCdfArcsineSpecialCase(): void
    {
        // For Beta(0.5, 0.5): CDF(x) = 2/pi * asin(sqrt(x))
        $beta = new Beta(0.5, 0.5);

        foreach ([0.05, 0.1, 0.25, 0.5, 0.75, 0.9, 0.95] as $x) {
            $expected = (2.0 / M_PI) * asin(sqrt($x));
            $this->assertEqualsWithDelta($expected, $beta->cdf($x), 1e-12);
        }
    }

    public function testCdfSymmetry(): void
    {
        // Symmetric Beta(a, a) must have CDF(0.5) = 0.5 and CDF(x) + CDF(1-x) = 1
        $symmetricParams = [0.5, 1.0, 2.0, 3.5, 5.0];
        foreach ($symmetricParams as $param) {
            $beta = new Beta($param, $param);
            $this->assertEqualsWithDelta(0.5, $beta->cdf(0.5), 1e-12);

            foreach ([0.1, 0.2, 0.35, 0.45] as $x) {
                $this->assertEqualsWithDelta(1.0, $beta->cdf($x) + $beta->cdf(1.0 - $x), 1e-12);
            }
        }

        // General symmetry: I_x(a, b) + I_{1-x}(b, a) = 1
        $betaAB = new Beta(2.5, 4.0);
        $betaBA = new Beta(4.0, 2.5);
        foreach ([0.1, 0.3, 0.5, 0.7, 0.85] as $x) {
            $this->assertEqualsWithDelta(1.0, $betaAB->cdf($x) + $betaBA->cdf(1.0 - $x), 1e-12);
        }
    }

    public function testCdfMonotonicity(): void
    {
        $testParams = [
            [0.5, 0.5],
            [1.0, 3.0],
            [2.0, 5.0],
            [3.0, 3.0],
            [5.0, 2.0],
        ];

        foreach ($testParams as [$alpha, $betaParam]) {
            $beta = new Beta($alpha, $betaParam);
            $previous = 0.0;

            for ($x = 0.05; $x <= 0.95; $x += 0.05) {
                $current = $beta->cdf($x);
                $this->assertGreaterThanOrEqual($previous, $current);
                $this->assertGreaterThanOrEqual(0.0, $current);
                $this->assertLessThanOrEqual(1.0, $current);
                $previous = $current;
            }
        }
    }

    public function testCdfKnownAnalyticalValues(): void
    {
        // Beta(2, 5) at x = 0.3 -> 0.579825
        $beta25 = new Beta(2.0, 5.0);
        $this->assertEqualsWithDelta(0.579825, $beta25->cdf(0.3), 1e-12);

        // Beta(3, 2) at x = 0.4 -> 0.1792
        $beta32 = new Beta(3.0, 2.0);
        $this->assertEqualsWithDelta(0.1792, $beta32->cdf(0.4), 1e-12);

        // Beta(2, 2) at x = 0.5 -> 0.5
        $beta22 = new Beta(2.0, 2.0);
        $this->assertEqualsWithDelta(0.5, $beta22->cdf(0.5), 1e-12);
    }

    public function testPdfOutsideSupportAndBoundaries(): void
    {
        $beta = new Beta(2.0, 3.0);
        $this->assertSame(0.0, $beta->pdf(-0.5));
        $this->assertSame(0.0, $beta->pdf(-0.001));
        $this->assertSame(0.0, $beta->pdf(1.001));
        $this->assertSame(0.0, $beta->pdf(2.0));

        // x = 0 boundaries
        $betaAlphaSub1 = new Beta(0.5, 2.0);
        $this->assertTrue(is_infinite($betaAlphaSub1->pdf(0.0)));

        $betaAlpha1 = new Beta(1.0, 4.0);
        $this->assertEqualsWithDelta(4.0, $betaAlpha1->pdf(0.0), 1e-12);

        $betaAlphaSuper1 = new Beta(2.0, 4.0);
        $this->assertSame(0.0, $betaAlphaSuper1->pdf(0.0));

        // x = 1 boundaries
        $betaBetaSub1 = new Beta(2.0, 0.5);
        $this->assertTrue(is_infinite($betaBetaSub1->pdf(1.0)));

        $betaBeta1 = new Beta(3.0, 1.0);
        $this->assertEqualsWithDelta(3.0, $betaBeta1->pdf(1.0), 1e-12);

        $betaBetaSuper1 = new Beta(3.0, 2.0);
        $this->assertSame(0.0, $betaBetaSuper1->pdf(1.0));
    }

    public function testPdfUniformSpecialCase(): void
    {
        $beta = new Beta(1.0, 1.0);

        foreach ([0.1, 0.25, 0.5, 0.75, 0.9] as $x) {
            $this->assertEqualsWithDelta(1.0, $beta->pdf($x), 1e-12);
        }
    }

    public function testPdfKnownValues(): void
    {
        // Beta(2, 5) at x = 0.3: f(0.3) = 0.3 * (0.7)^4 / (1/30) = 2.1609
        $beta25 = new Beta(2.0, 5.0);
        $this->assertEqualsWithDelta(2.1609, $beta25->pdf(0.3), 1e-12);

        // Beta(3, 2) at x = 0.4: f(0.4) = (0.4)^2 * (0.6)^1 / (1/12) = 1.152
        $beta32 = new Beta(3.0, 2.0);
        $this->assertEqualsWithDelta(1.152, $beta32->pdf(0.4), 1e-12);

        // Beta(3, 3) at x = 0.5: f(0.5) = (0.5)^2 * (0.5)^2 / (1/30) = 1.875
        $beta33 = new Beta(3.0, 3.0);
        $this->assertEqualsWithDelta(1.875, $beta33->pdf(0.5), 1e-12);
    }

    public function testPdfSymmetry(): void
    {
        // For Beta(a, a), PDF is symmetric around 0.5: f(x) = f(1-x)
        $symmetric = new Beta(2.5, 2.5);
        foreach ([0.1, 0.2, 0.35, 0.45] as $x) {
            $this->assertEqualsWithDelta($symmetric->pdf($x), $symmetric->pdf(1.0 - $x), 1e-12);
        }

        // For Beta(a, b) vs Beta(b, a): f(x; a, b) = f(1-x; b, a)
        $betaAB = new Beta(2.0, 4.0);
        $betaBA = new Beta(4.0, 2.0);
        foreach ([0.1, 0.25, 0.5, 0.75, 0.9] as $x) {
            $this->assertEqualsWithDelta($betaAB->pdf($x), $betaBA->pdf(1.0 - $x), 1e-12);
        }
    }
}

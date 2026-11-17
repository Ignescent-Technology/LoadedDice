<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests\Distributions\Continuous;

use Ignescent\LoadedDice\Distributions\Continuous\Distribution as ContinuousDistribution;
use Ignescent\LoadedDice\Distributions\Continuous\Gamma;
use Ignescent\LoadedDice\Distributions\Distribution;
use Ignescent\LoadedDice\Utils;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

class GammaTest extends TestCase
{
    public function testImplementsDistributionInterfaces(): void
    {
        $gamma = new Gamma(new Randomizer(), 2.0, 1.5);

        $this->assertInstanceOf(Distribution::class, $gamma);
        $this->assertInstanceOf(ContinuousDistribution::class, $gamma);
    }

    public function testConstructorSetsProperties(): void
    {
        $gamma = new Gamma(new Randomizer(), 2.5, 3.5);

        $this->assertSame(2.5, $gamma->shape);
        $this->assertSame(3.5, $gamma->scale);
    }

    public function testRandomReturnsPositiveFloat(): void
    {
        $gamma = new Gamma(new Randomizer(new Mt19937(42)), 2.0, 1.5);

        for ($i = 0; $i < 50; $i++) {
            $value = $gamma->random();
            $this->assertIsFloat($value);
            $this->assertGreaterThan(0.0, $value);
        }
    }

    public function testDeterministicWithSameSeed(): void
    {
        $gamma1 = new Gamma(new Randomizer(new Mt19937(12345)), 3.0, 2.0);
        $gamma2 = new Gamma(new Randomizer(new Mt19937(12345)), 3.0, 2.0);

        for ($i = 0; $i < 20; $i++) {
            $this->assertSame($gamma1->random(), $gamma2->random());
        }
    }

    public function testCdfBoundaryConditions(): void
    {
        $gamma = new Gamma(new Randomizer(), 2.0, 1.5);

        $this->assertSame(0.0, $gamma->cdf(-5.0));
        $this->assertSame(0.0, $gamma->cdf(-0.001));
        $this->assertSame(0.0, $gamma->cdf(0.0));
    }

    public function testCdfExponentialSpecialCase(): void
    {
        // Gamma(1, scale) is Exponential with lambda = 1 / scale
        $scale = 2.5;
        $gamma = new Gamma(new Randomizer(), 1.0, $scale);

        foreach ([0.1, 0.5, 1.0, 2.5, 5.0, 10.0] as $x) {
            $expected = 1.0 - exp(-$x / $scale);
            $this->assertEqualsWithDelta($expected, $gamma->cdf($x), 1e-12);
        }
    }

    public function testCdfKnownValues(): void
    {
        // Gamma(2, 2): CDF(2) = 1 - 2*exp(-1)
        $gamma1 = new Gamma(new Randomizer(), 2.0, 2.0);
        $this->assertEqualsWithDelta(1.0 - 2.0 * exp(-1.0), $gamma1->cdf(2.0), 1e-12);

        // Gamma(3, 1): CDF(1) = 1 - 2.5*exp(-1)
        $gamma2 = new Gamma(new Randomizer(), 3.0, 1.0);
        $this->assertEqualsWithDelta(1.0 - 2.5 * exp(-1.0), $gamma2->cdf(1.0), 1e-12);

        // Gamma(0.5, 2): Chi-squared with 1 df. CDF(1) = erf(1 / sqrt(2)) ≈ 0.682689492137086
        $gamma3 = new Gamma(new Randomizer(), 0.5, 2.0);
        $this->assertEqualsWithDelta(0.682689492137086, $gamma3->cdf(1.0), 1e-12);
        $this->assertEqualsWithDelta(Utils::erf(1.0 / M_SQRT2), $gamma3->cdf(1.0), 1e-6);
    }

    public function testCdfMonotonicity(): void
    {
        $gamma = new Gamma(new Randomizer(), 2.5, 1.5);

        $prev = -1.0;
        for ($x = 0.0; $x <= 20.0; $x += 0.5) {
            $cdf = $gamma->cdf($x);
            $this->assertGreaterThanOrEqual($prev, $cdf);
            $this->assertGreaterThanOrEqual(0.0, $cdf);
            $this->assertLessThanOrEqual(1.0, $cdf);
            $prev = $cdf;
        }
    }

    public function testCdfAsymptoticUpperLimit(): void
    {
        $gamma = new Gamma(new Randomizer(), 2.0, 1.5);

        $this->assertEqualsWithDelta(1.0, $gamma->cdf(100.0), 1e-12);
        $this->assertEqualsWithDelta(1.0, $gamma->cdf(1000.0), 1e-12);
    }

    public function testPdfBoundaryAndEdgeCases(): void
    {
        // Negative x returns 0.0
        $gammaShapeLt1 = new Gamma(new Randomizer(), 0.5, 2.0);
        $gammaShapeEq1 = new Gamma(new Randomizer(), 1.0, 2.0);
        $gammaShapeGt1 = new Gamma(new Randomizer(), 2.5, 2.0);

        $this->assertSame(0.0, $gammaShapeLt1->pdf(-1.0));
        $this->assertSame(0.0, $gammaShapeEq1->pdf(-1.0));
        $this->assertSame(0.0, $gammaShapeGt1->pdf(-1.0));

        // x = 0 behavior depends on shape
        $this->assertSame(INF, $gammaShapeLt1->pdf(0.0));
        $this->assertSame(1.0 / 2.0, $gammaShapeEq1->pdf(0.0));
        $this->assertSame(0.0, $gammaShapeGt1->pdf(0.0));
    }

    public function testPdfExponentialSpecialCase(): void
    {
        // Gamma(1, scale) is Exponential with lambda = 1 / scale
        $scale = 3.0;
        $gamma = new Gamma(new Randomizer(), 1.0, $scale);

        foreach ([0.0, 0.5, 1.0, 2.0, 4.0, 6.0] as $x) {
            $expected = (1.0 / $scale) * exp(-$x / $scale);
            $this->assertEqualsWithDelta($expected, $gamma->pdf($x), 1e-12);
        }
    }

    public function testPdfKnownValues(): void
    {
        // Gamma(2, 2): f(2) = 2 * exp(-1) / 4 = 1 / (2*e)
        $gamma1 = new Gamma(new Randomizer(), 2.0, 2.0);
        $this->assertEqualsWithDelta(1.0 / (2.0 * M_E), $gamma1->pdf(2.0), 1e-12);

        // Gamma(3, 1): f(1) = 1^2 * exp(-1) / 2! = 1 / (2*e)
        $gamma2 = new Gamma(new Randomizer(), 3.0, 1.0);
        $this->assertEqualsWithDelta(1.0 / (2.0 * M_E), $gamma2->pdf(1.0), 1e-12);

        // Gamma(0.5, 2): f(1) = exp(-0.5) / sqrt(2*pi)
        $gamma3 = new Gamma(new Randomizer(), 0.5, 2.0);
        $this->assertEqualsWithDelta(exp(-0.5) / sqrt(2.0 * M_PI), $gamma3->pdf(1.0), 1e-12);
    }

    public function testPdfNumericalStabilityWithLargeParameters(): void
    {
        // Large shape parameter that would overflow in direct power/gamma computation
        $gammaLarge = new Gamma(new Randomizer(), 100.0, 2.0);

        // Peak / mode of Gamma(k, theta) is (k - 1) * theta = 99 * 2 = 198
        $peakPdf = $gammaLarge->pdf(198.0);
        $this->assertIsFloat($peakPdf);
        $this->assertFalse(is_nan($peakPdf));
        $this->assertFalse(is_infinite($peakPdf));
        $this->assertGreaterThan(0.0, $peakPdf);

        // Far right tail should smoothly evaluate to zero or near-zero without underflow/overflow exception or NAN
        $tailPdf = $gammaLarge->pdf(1000.0);
        $this->assertIsFloat($tailPdf);
        $this->assertLessThan(1e-100, $tailPdf);
        $this->assertGreaterThanOrEqual(0.0, $tailPdf);

        // Extreme tail where logPdf < -745 underflows cleanly to 0.0
        $extremeTailPdf = $gammaLarge->pdf(3000.0);
        $this->assertSame(0.0, $extremeTailPdf);

        // Shape = 200 where direct evaluation of x^(k-1), scale^k, or gamma(k) would overflow double float range
        $gammaExtreme = new Gamma(new Randomizer(), 200.0, 1.0);
        $extremePeak = $gammaExtreme->pdf(199.0);
        $this->assertIsFloat($extremePeak);
        $this->assertFalse(is_nan($extremePeak));
        $this->assertFalse(is_infinite($extremePeak));
        $this->assertGreaterThan(0.0, $extremePeak);
    }

    public function testPdfNumericalIntegralApproximation(): void
    {
        $gamma = new Gamma(new Randomizer(), 3.0, 2.0);

        // Integrate PDF from 0 to 30 via trapezoidal rule
        $a = 0.001;
        $b = 30.0;
        $n = 10000;
        $h = ($b - $a) / $n;
        $sum = 0.5 * ($gamma->pdf($a) + $gamma->pdf($b));

        for ($i = 1; $i < $n; $i++) {
            $sum += $gamma->pdf($a + $i * $h);
        }

        $integral = $sum * $h;
        $this->assertEqualsWithDelta(1.0, $integral, 1e-4);
    }
}

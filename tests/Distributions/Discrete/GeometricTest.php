<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests\Distributions\Discrete;

use Ignescent\LoadedDice\Distributions\Discrete\Distribution as DiscreteDistribution;
use Ignescent\LoadedDice\Distributions\Discrete\Geometric;
use Ignescent\LoadedDice\Distributions\Distribution;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

class GeometricTest extends TestCase
{
    public function testImplementsDistributionInterfaces(): void
    {
        $geometric = new Geometric(new Randomizer(), 0.5);

        $this->assertInstanceOf(Distribution::class, $geometric);
        $this->assertInstanceOf(DiscreteDistribution::class, $geometric);
    }

    public function testConstructorSetsProperties(): void
    {
        $geometric = new Geometric(new Randomizer(), 0.25);

        $this->assertSame(0.25, $geometric->p);
    }

    public function testRandomReturnsNonNegativeInt(): void
    {
        $geometric = new Geometric(new Randomizer(new Mt19937(42)), 0.3);

        for ($i = 0; $i < 50; $i++) {
            $value = $geometric->random();
            $this->assertIsInt($value);
            $this->assertGreaterThanOrEqual(0, $value);
        }
    }

    public function testFullProbabilityAlwaysReturnsZero(): void
    {
        $geometric = new Geometric(new Randomizer(new Mt19937(42)), 1.0);

        for ($i = 0; $i < 20; $i++) {
            $this->assertSame(0, $geometric->random());
        }
    }

    public function testDeterministicWithSameSeed(): void
    {
        $geometric1 = new Geometric(new Randomizer(new Mt19937(12345)), 0.4);
        $geometric2 = new Geometric(new Randomizer(new Mt19937(12345)), 0.4);

        for ($i = 0; $i < 20; $i++) {
            $this->assertSame($geometric1->random(), $geometric2->random());
        }
    }

    public function testDistributionMeanApproximation(): void
    {
        $p = 0.5;
        $geometric = new Geometric(new Randomizer(new Mt19937(42)), $p);
        $samples = 5000;
        $sum = 0;

        for ($i = 0; $i < $samples; $i++) {
            $sum += $geometric->random();
        }

        $mean = $sum / $samples;
        $expectedMean = (1.0 - $p) / $p; // 1.0
        $this->assertEqualsWithDelta($expectedMean, $mean, 0.08);
    }

    public function testPmfKnownValues(): void
    {
        // Geometric(p=0.5): PMF(k) = (0.5)^k * 0.5
        $geometric = new Geometric(new Randomizer(), 0.5);

        $this->assertEqualsWithDelta(0.5, $geometric->pmf(0), 1e-12);
        $this->assertEqualsWithDelta(0.25, $geometric->pmf(1), 1e-12);
        $this->assertEqualsWithDelta(0.125, $geometric->pmf(2), 1e-12);
        $this->assertEqualsWithDelta(0.0625, $geometric->pmf(3), 1e-12);

        // Geometric(p=0.2): PMF(0)=0.2, PMF(1)=0.16, PMF(2)=0.128
        $geometric2 = new Geometric(new Randomizer(), 0.2);
        $this->assertEqualsWithDelta(0.2, $geometric2->pmf(0), 1e-12);
        $this->assertEqualsWithDelta(0.16, $geometric2->pmf(1), 1e-12);
        $this->assertEqualsWithDelta(0.128, $geometric2->pmf(2), 1e-12);
    }

    public function testPmfBoundaryAndEdgeCases(): void
    {
        $geometric = new Geometric(new Randomizer(), 0.4);

        $this->assertSame(0.0, $geometric->pmf(-1));
        $this->assertSame(0.0, $geometric->pmf(-10));

        // p = 1.0 edge case
        $fullProb = new Geometric(new Randomizer(), 1.0);
        $this->assertSame(1.0, $fullProb->pmf(0));
        $this->assertSame(0.0, $fullProb->pmf(1));
        $this->assertSame(0.0, $fullProb->pmf(5));

        // p <= 0.0 edge case
        $zeroProb = new Geometric(new Randomizer(), 0.0);
        $this->assertSame(0.0, $zeroProb->pmf(0));
        $this->assertSame(0.0, $zeroProb->pmf(1));

        // Large k should not throw or return NaN
        $largePmf = $geometric->pmf(1000);
        $this->assertIsFloat($largePmf);
        $this->assertGreaterThanOrEqual(0.0, $largePmf);
        $this->assertLessThan(1e-100, $largePmf);
    }

    public function testCdfKnownValues(): void
    {
        // Geometric(p=0.5): CDF(k) = 1 - 0.5^(k+1)
        $geometric = new Geometric(new Randomizer(), 0.5);

        $this->assertEqualsWithDelta(0.5, $geometric->cdf(0), 1e-12);
        $this->assertEqualsWithDelta(0.75, $geometric->cdf(1), 1e-12);
        $this->assertEqualsWithDelta(0.875, $geometric->cdf(2), 1e-12);
        $this->assertEqualsWithDelta(0.9375, $geometric->cdf(3), 1e-12);
    }

    public function testCdfBoundaryAndEdgeCases(): void
    {
        $geometric = new Geometric(new Randomizer(), 0.3);

        $this->assertSame(0.0, $geometric->cdf(-1));
        $this->assertSame(0.0, $geometric->cdf(-5));

        // p = 1.0 edge case
        $fullProb = new Geometric(new Randomizer(), 1.0);
        $this->assertSame(0.0, $fullProb->cdf(-1));
        $this->assertSame(1.0, $fullProb->cdf(0));
        $this->assertSame(1.0, $fullProb->cdf(5));

        // p <= 0.0 edge case
        $zeroProb = new Geometric(new Randomizer(), 0.0);
        $this->assertSame(0.0, $zeroProb->cdf(0));
        $this->assertSame(0.0, $zeroProb->cdf(5));

        // Asymptotic upper limit
        $this->assertEqualsWithDelta(1.0, $geometric->cdf(100), 1e-12);
    }

    public function testCdfMatchesCumulativePmfSum(): void
    {
        $geometric = new Geometric(new Randomizer(), 0.35);

        $cumulativePmf = 0.0;
        for ($k = 0; $k <= 20; $k++) {
            $cumulativePmf += $geometric->pmf($k);
            $this->assertEqualsWithDelta($cumulativePmf, $geometric->cdf($k), 1e-12);
        }
    }
}

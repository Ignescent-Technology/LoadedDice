<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests\Distributions\Discrete;

use Ignescent\LoadedDice\Distributions\Discrete\Binomial;
use Ignescent\LoadedDice\Distributions\Discrete\Distribution as DiscreteDistribution;
use Ignescent\LoadedDice\Distributions\Distribution;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

class BinomialTest extends TestCase
{
    public function testImplementsDistributionInterfaces(): void
    {
        $binomial = new Binomial(new Randomizer(), 10, 0.5);

        $this->assertInstanceOf(Distribution::class, $binomial);
        $this->assertInstanceOf(DiscreteDistribution::class, $binomial);
    }

    public function testConstructorSetsProperties(): void
    {
        $binomial = new Binomial(new Randomizer(), 20, 0.7);

        $this->assertSame(20, $binomial->n);
        $this->assertSame(0.7, $binomial->p);
    }

    public function testRandomReturnsIntWithinBounds(): void
    {
        $binomial = new Binomial(new Randomizer(new Mt19937(42)), 10, 0.4);

        for ($i = 0; $i < 50; $i++) {
            $value = $binomial->random();
            $this->assertIsInt($value);
            $this->assertGreaterThanOrEqual(0, $value);
            $this->assertLessThanOrEqual(10, $value);
        }
    }

    public function testZeroProbabilityAlwaysReturnsZero(): void
    {
        $binomial = new Binomial(new Randomizer(new Mt19937(42)), 10, 0.0);

        for ($i = 0; $i < 20; $i++) {
            $this->assertSame(0, $binomial->random());
        }
    }

    public function testFullProbabilityAlwaysReturnsN(): void
    {
        $n = 15;
        $binomial = new Binomial(new Randomizer(new Mt19937(42)), $n, 1.0);

        for ($i = 0; $i < 20; $i++) {
            $this->assertSame($n, $binomial->random());
        }
    }

    public function testZeroTrialsReturnsZero(): void
    {
        $binomial = new Binomial(new Randomizer(new Mt19937(42)), 0, 0.5);

        for ($i = 0; $i < 10; $i++) {
            $this->assertSame(0, $binomial->random());
        }
    }

    public function testDeterministicWithSameSeed(): void
    {
        $binomial1 = new Binomial(new Randomizer(new Mt19937(12345)), 10, 0.3);
        $binomial2 = new Binomial(new Randomizer(new Mt19937(12345)), 10, 0.3);

        for ($i = 0; $i < 20; $i++) {
            $this->assertSame($binomial1->random(), $binomial2->random());
        }
    }

    public function testDistributionMeanApproximation(): void
    {
        $n = 20;
        $p = 0.3;
        $binomial = new Binomial(new Randomizer(new Mt19937(42)), $n, $p);
        $samples = 5000;
        $sum = 0;

        for ($i = 0; $i < $samples; $i++) {
            $sum += $binomial->random();
        }

        $mean = $sum / $samples;
        $expectedMean = $n * $p; // 6.0
        $this->assertEqualsWithDelta($expectedMean, $mean, 0.1);
    }

    public function testPmfKnownValues(): void
    {
        // Binomial(n=4, p=0.5): PMF = [1/16, 4/16, 6/16, 4/16, 1/16]
        $binomial = new Binomial(new Randomizer(), 4, 0.5);

        $this->assertEqualsWithDelta(1.0 / 16.0, $binomial->pmf(0), 1e-12);
        $this->assertEqualsWithDelta(4.0 / 16.0, $binomial->pmf(1), 1e-12);
        $this->assertEqualsWithDelta(6.0 / 16.0, $binomial->pmf(2), 1e-12);
        $this->assertEqualsWithDelta(4.0 / 16.0, $binomial->pmf(3), 1e-12);
        $this->assertEqualsWithDelta(1.0 / 16.0, $binomial->pmf(4), 1e-12);

        // Sum of all PMF values equals 1.0
        $sum = 0.0;
        for ($k = 0; $k <= 4; $k++) {
            $sum += $binomial->pmf($k);
        }
        $this->assertEqualsWithDelta(1.0, $sum, 1e-12);
    }

    public function testPmfBoundaryAndEdgeCases(): void
    {
        $binomial = new Binomial(new Randomizer(), 5, 0.4);

        $this->assertSame(0.0, $binomial->pmf(-1));
        $this->assertSame(0.0, $binomial->pmf(-5));
        $this->assertSame(0.0, $binomial->pmf(6));
        $this->assertSame(0.0, $binomial->pmf(100));

        // p = 0.0 edge case
        $zeroProb = new Binomial(new Randomizer(), 10, 0.0);
        $this->assertSame(1.0, $zeroProb->pmf(0));
        $this->assertSame(0.0, $zeroProb->pmf(1));

        // p = 1.0 edge case
        $fullProb = new Binomial(new Randomizer(), 10, 1.0);
        $this->assertSame(1.0, $fullProb->pmf(10));
        $this->assertSame(0.0, $fullProb->pmf(9));

        // n = 0 edge case
        $zeroTrials = new Binomial(new Randomizer(), 0, 0.5);
        $this->assertSame(1.0, $zeroTrials->pmf(0));
        $this->assertSame(0.0, $zeroTrials->pmf(1));
    }

    public function testPmfNumericalStabilityWithLargeN(): void
    {
        $binomial = new Binomial(new Randomizer(), 100, 0.3);

        // Expected mode around k = 30
        $pmf30 = $binomial->pmf(30);
        $this->assertIsFloat($pmf30);
        $this->assertGreaterThan(0.08, $pmf30);
        $this->assertLessThan(0.10, $pmf30);

        // Total mass across all outcomes sums to 1.0
        $sum = 0.0;
        for ($k = 0; $k <= 100; $k++) {
            $sum += $binomial->pmf($k);
        }
        $this->assertEqualsWithDelta(1.0, $sum, 1e-12);
    }

    public function testCdfKnownValues(): void
    {
        // Binomial(n=4, p=0.5): CDF = [1/16, 5/16, 11/16, 15/16, 1.0]
        $binomial = new Binomial(new Randomizer(), 4, 0.5);

        $this->assertEqualsWithDelta(1.0 / 16.0, $binomial->cdf(0), 1e-12);
        $this->assertEqualsWithDelta(5.0 / 16.0, $binomial->cdf(1), 1e-12);
        $this->assertEqualsWithDelta(11.0 / 16.0, $binomial->cdf(2), 1e-12);
        $this->assertEqualsWithDelta(15.0 / 16.0, $binomial->cdf(3), 1e-12);
        $this->assertEqualsWithDelta(1.0, $binomial->cdf(4), 1e-12);
    }

    public function testCdfBoundaryAndEdgeCases(): void
    {
        $binomial = new Binomial(new Randomizer(), 5, 0.4);

        $this->assertSame(0.0, $binomial->cdf(-1));
        $this->assertSame(0.0, $binomial->cdf(-10));
        $this->assertSame(1.0, $binomial->cdf(5));
        $this->assertSame(1.0, $binomial->cdf(6));
        $this->assertSame(1.0, $binomial->cdf(100));

        // p = 0.0 edge case
        $zeroProb = new Binomial(new Randomizer(), 10, 0.0);
        $this->assertSame(0.0, $zeroProb->cdf(-1));
        $this->assertSame(1.0, $zeroProb->cdf(0));
        $this->assertSame(1.0, $zeroProb->cdf(5));

        // p = 1.0 edge case
        $fullProb = new Binomial(new Randomizer(), 10, 1.0);
        $this->assertSame(0.0, $fullProb->cdf(9));
        $this->assertSame(1.0, $fullProb->cdf(10));
        $this->assertSame(1.0, $fullProb->cdf(15));
    }

    public function testCdfMatchesCumulativePmfSum(): void
    {
        $n = 10;
        $binomial = new Binomial(new Randomizer(), $n, 0.35);

        $cumulativePmf = 0.0;
        for ($k = 0; $k <= $n; $k++) {
            $cumulativePmf += $binomial->pmf($k);
            $this->assertEqualsWithDelta($cumulativePmf, $binomial->cdf($k), 1e-12);
        }
    }
}

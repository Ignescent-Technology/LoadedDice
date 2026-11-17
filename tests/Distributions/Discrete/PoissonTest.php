<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests\Distributions\Discrete;

use Ignescent\LoadedDice\Distributions\Discrete\Distribution as DiscreteDistribution;
use Ignescent\LoadedDice\Distributions\Discrete\Poisson;
use Ignescent\LoadedDice\Distributions\Distribution;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

class PoissonTest extends TestCase
{
    public function testImplementsDistributionInterfaces(): void
    {
        $poisson = new Poisson(new Randomizer(), 4.0);

        $this->assertInstanceOf(Distribution::class, $poisson);
        $this->assertInstanceOf(DiscreteDistribution::class, $poisson);
    }

    public function testConstructorSetsProperties(): void
    {
        $poisson = new Poisson(new Randomizer(), 5.5);

        $this->assertSame(5.5, $poisson->lambda);
    }

    public function testRandomReturnsNonNegativeInt(): void
    {
        $poisson = new Poisson(new Randomizer(new Mt19937(42)), 4.0);

        for ($i = 0; $i < 50; $i++) {
            $value = $poisson->random();
            $this->assertIsInt($value);
            $this->assertGreaterThanOrEqual(0, $value);
        }
    }

    public function testDeterministicWithSameSeed(): void
    {
        $poisson1 = new Poisson(new Randomizer(new Mt19937(12345)), 3.5);
        $poisson2 = new Poisson(new Randomizer(new Mt19937(12345)), 3.5);

        for ($i = 0; $i < 20; $i++) {
            $this->assertSame($poisson1->random(), $poisson2->random());
        }
    }

    public function testDistributionMeanApproximation(): void
    {
        $lambda = 4.0;
        $poisson = new Poisson(new Randomizer(new Mt19937(42)), $lambda);
        $samples = 5000;
        $sum = 0;

        for ($i = 0; $i < $samples; $i++) {
            $sum += $poisson->random();
        }

        $mean = $sum / $samples;
        $this->assertEqualsWithDelta($lambda, $mean, 0.1);
    }

    public function testLargeLambdaSupportsStepThreshold(): void
    {
        $lambda = 600.0;
        $poisson = new Poisson(new Randomizer(new Mt19937(42)), $lambda);

        $value = $poisson->random();
        $this->assertIsInt($value);
        $this->assertGreaterThan(0, $value);
    }

    public function testPmfKnownValues(): void
    {
        // Poisson(lambda = 2.0)
        // PMF(0) = e^-2
        // PMF(1) = 2 * e^-2
        // PMF(2) = 2 * e^-2
        // PMF(3) = (4/3) * e^-2
        $poisson = new Poisson(new Randomizer(), 2.0);

        $this->assertEqualsWithDelta(exp(-2.0), $poisson->pmf(0), 1e-12);
        $this->assertEqualsWithDelta(2.0 * exp(-2.0), $poisson->pmf(1), 1e-12);
        $this->assertEqualsWithDelta(2.0 * exp(-2.0), $poisson->pmf(2), 1e-12);
        $this->assertEqualsWithDelta((4.0 / 3.0) * exp(-2.0), $poisson->pmf(3), 1e-12);

        // Sum of PMF over 0..25 sums to 1.0
        $sum = 0.0;
        for ($k = 0; $k <= 25; $k++) {
            $sum += $poisson->pmf($k);
        }
        $this->assertEqualsWithDelta(1.0, $sum, 1e-12);
    }

    public function testPmfBoundaryAndEdgeCases(): void
    {
        $poisson = new Poisson(new Randomizer(), 3.0);

        $this->assertSame(0.0, $poisson->pmf(-1));
        $this->assertSame(0.0, $poisson->pmf(-10));

        // lambda <= 0.0 edge case
        $zeroLambda = new Poisson(new Randomizer(), 0.0);
        $this->assertSame(1.0, $zeroLambda->pmf(0));
        $this->assertSame(0.0, $zeroLambda->pmf(1));
    }

    public function testPmfNumericalStabilityWithLargeParameters(): void
    {
        $poisson = new Poisson(new Randomizer(), 100.0);

        // Peak / mode around lambda = 100
        $peakPmf = $poisson->pmf(100);
        $this->assertIsFloat($peakPmf);
        $this->assertGreaterThan(0.03, $peakPmf);
        $this->assertLessThan(0.05, $peakPmf);

        // Tail underflows safely
        $tailPmf = $poisson->pmf(500);
        $this->assertIsFloat($tailPmf);
        $this->assertLessThan(1e-100, $tailPmf);
        $this->assertGreaterThanOrEqual(0.0, $tailPmf);
    }

    public function testCdfKnownValues(): void
    {
        // Poisson(lambda = 2.0)
        // CDF(0) = e^-2
        // CDF(1) = 3 * e^-2
        // CDF(2) = 5 * e^-2
        // CDF(3) = (19/3) * e^-2
        $poisson = new Poisson(new Randomizer(), 2.0);

        $this->assertEqualsWithDelta(exp(-2.0), $poisson->cdf(0), 1e-12);
        $this->assertEqualsWithDelta(3.0 * exp(-2.0), $poisson->cdf(1), 1e-12);
        $this->assertEqualsWithDelta(5.0 * exp(-2.0), $poisson->cdf(2), 1e-12);
        $this->assertEqualsWithDelta((19.0 / 3.0) * exp(-2.0), $poisson->cdf(3), 1e-12);
    }

    public function testCdfBoundaryAndEdgeCases(): void
    {
        $poisson = new Poisson(new Randomizer(), 2.5);

        $this->assertSame(0.0, $poisson->cdf(-1));
        $this->assertSame(0.0, $poisson->cdf(-10));

        // lambda <= 0.0 edge case
        $zeroLambda = new Poisson(new Randomizer(), 0.0);
        $this->assertSame(0.0, $zeroLambda->cdf(-1));
        $this->assertSame(1.0, $zeroLambda->cdf(0));
        $this->assertSame(1.0, $zeroLambda->cdf(5));

        // Asymptotic upper limit
        $this->assertEqualsWithDelta(1.0, $poisson->cdf(30), 1e-12);
    }

    public function testCdfMatchesCumulativePmfSum(): void
    {
        $poisson = new Poisson(new Randomizer(), 3.5);

        $cumulativePmf = 0.0;
        for ($k = 0; $k <= 20; $k++) {
            $cumulativePmf += $poisson->pmf($k);
            $this->assertEqualsWithDelta($cumulativePmf, $poisson->cdf($k), 1e-12);
        }
    }
}

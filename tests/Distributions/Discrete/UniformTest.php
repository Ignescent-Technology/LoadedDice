<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests\Distributions\Discrete;

use Ignescent\LoadedDice\Distributions\Discrete\Distribution as DiscreteDistribution;
use Ignescent\LoadedDice\Distributions\Discrete\Uniform;
use Ignescent\LoadedDice\Distributions\Distribution;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

class UniformTest extends TestCase
{
    public function testImplementsDistributionInterfaces(): void
    {
        $uniform = new Uniform(1, 6);

        $this->assertInstanceOf(Distribution::class, $uniform);
        $this->assertInstanceOf(DiscreteDistribution::class, $uniform);
    }

    public function testConstructorSetsProperties(): void
    {
        $uniform = new Uniform(10, 20);

        $this->assertSame(10, $uniform->min);
        $this->assertSame(20, $uniform->max);
    }

    public function testConstructorDefaultRandomizer(): void
    {
        $uniform = new Uniform(1, 6);
        $val = $uniform->random();

        $this->assertIsInt($val);
        $this->assertGreaterThanOrEqual(1, $val);
        $this->assertLessThanOrEqual(6, $val);
    }

    public function testRandomReturnsIntWithinBounds(): void
    {
        $uniform = new Uniform(1, 6, new Randomizer(new Mt19937(42)));

        for ($i = 0; $i < 50; $i++) {
            $value = $uniform->random();
            $this->assertIsInt($value);
            $this->assertGreaterThanOrEqual(1, $value);
            $this->assertLessThanOrEqual(6, $value);
        }
    }

    public function testRandomWithEqualMinAndMax(): void
    {
        $uniform = new Uniform(7, 7, new Randomizer(new Mt19937(42)));

        for ($i = 0; $i < 10; $i++) {
            $this->assertSame(7, $uniform->random());
        }
    }

    public function testDeterministicWithSameSeed(): void
    {
        $uniform1 = new Uniform(1, 100, new Randomizer(new Mt19937(12345)));
        $uniform2 = new Uniform(1, 100, new Randomizer(new Mt19937(12345)));

        for ($i = 0; $i < 20; $i++) {
            $this->assertSame($uniform1->random(), $uniform2->random());
        }
    }

    public function testDistributionMeanApproximation(): void
    {
        $uniform = new Uniform(1, 6, new Randomizer(new Mt19937(42)));
        $samples = 5000;
        $sum = 0;

        for ($i = 0; $i < $samples; $i++) {
            $sum += $uniform->random();
        }

        $mean = $sum / $samples;
        $expectedMean = (1 + 6) / 2.0; // 3.5
        $this->assertEqualsWithDelta($expectedMean, $mean, 0.1);
    }

    public function testPmfKnownValues(): void
    {
        // Standard six-sided die Uniform(1, 6): PMF = 1/6 for all k in 1..6
        $uniform = new Uniform(1, 6);

        for ($k = 1; $k <= 6; $k++) {
            $this->assertEqualsWithDelta(1.0 / 6.0, $uniform->pmf($k), 1e-12);
        }

        // Single outcome Uniform(5, 5)
        $singlePoint = new Uniform(5, 5);
        $this->assertSame(1.0, $singlePoint->pmf(5));

        // Range with negative values Uniform(-2, 2) has 5 outcomes
        $symmetric = new Uniform(-2, 2);
        for ($k = -2; $k <= 2; $k++) {
            $this->assertEqualsWithDelta(0.2, $symmetric->pmf($k), 1e-12);
        }
    }

    public function testPmfBoundaryCases(): void
    {
        $uniform = new Uniform(2, 7);

        $this->assertSame(0.0, $uniform->pmf(1));
        $this->assertSame(0.0, $uniform->pmf(0));
        $this->assertSame(0.0, $uniform->pmf(-5));
        $this->assertSame(0.0, $uniform->pmf(8));
        $this->assertSame(0.0, $uniform->pmf(20));
    }

    public function testCdfKnownValues(): void
    {
        // Standard die Uniform(1, 6)
        $uniform = new Uniform(1, 6);

        $this->assertEqualsWithDelta(1.0 / 6.0, $uniform->cdf(1), 1e-12);
        $this->assertEqualsWithDelta(2.0 / 6.0, $uniform->cdf(2), 1e-12);
        $this->assertEqualsWithDelta(3.0 / 6.0, $uniform->cdf(3), 1e-12);
        $this->assertEqualsWithDelta(4.0 / 6.0, $uniform->cdf(4), 1e-12);
        $this->assertEqualsWithDelta(5.0 / 6.0, $uniform->cdf(5), 1e-12);
        $this->assertEqualsWithDelta(1.0, $uniform->cdf(6), 1e-12);
    }

    public function testCdfBoundaryCases(): void
    {
        $uniform = new Uniform(3, 8);

        $this->assertSame(0.0, $uniform->cdf(2));
        $this->assertSame(0.0, $uniform->cdf(0));
        $this->assertSame(0.0, $uniform->cdf(-10));
        $this->assertSame(1.0, $uniform->cdf(8));
        $this->assertSame(1.0, $uniform->cdf(9));
        $this->assertSame(1.0, $uniform->cdf(100));
    }

    public function testCdfMatchesCumulativePmfSum(): void
    {
        $uniform = new Uniform(-3, 5);

        $cumulativePmf = 0.0;
        for ($k = -3; $k <= 5; $k++) {
            $cumulativePmf += $uniform->pmf($k);
            $this->assertEqualsWithDelta($cumulativePmf, $uniform->cdf($k), 1e-12);
        }
    }
}

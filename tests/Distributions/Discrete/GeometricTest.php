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
}

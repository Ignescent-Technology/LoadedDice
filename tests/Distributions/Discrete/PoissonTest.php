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
}

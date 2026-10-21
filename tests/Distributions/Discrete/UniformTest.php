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
        $uniform = new Uniform(new Randomizer(), 1, 6);

        $this->assertInstanceOf(Distribution::class, $uniform);
        $this->assertInstanceOf(DiscreteDistribution::class, $uniform);
    }

    public function testConstructorSetsProperties(): void
    {
        $uniform = new Uniform(new Randomizer(), 10, 20);

        $this->assertSame(10, $uniform->min);
        $this->assertSame(20, $uniform->max);
    }

    public function testRandomReturnsIntWithinBounds(): void
    {
        $uniform = new Uniform(new Randomizer(new Mt19937(42)), 1, 6);

        for ($i = 0; $i < 50; $i++) {
            $value = $uniform->random();
            $this->assertIsInt($value);
            $this->assertGreaterThanOrEqual(1, $value);
            $this->assertLessThanOrEqual(6, $value);
        }
    }

    public function testRandomWithEqualMinAndMax(): void
    {
        $uniform = new Uniform(new Randomizer(new Mt19937(42)), 7, 7);

        for ($i = 0; $i < 10; $i++) {
            $this->assertSame(7, $uniform->random());
        }
    }

    public function testDeterministicWithSameSeed(): void
    {
        $uniform1 = new Uniform(new Randomizer(new Mt19937(12345)), 1, 100);
        $uniform2 = new Uniform(new Randomizer(new Mt19937(12345)), 1, 100);

        for ($i = 0; $i < 20; $i++) {
            $this->assertSame($uniform1->random(), $uniform2->random());
        }
    }

    public function testDistributionMeanApproximation(): void
    {
        $uniform = new Uniform(new Randomizer(new Mt19937(42)), 1, 6);
        $samples = 5000;
        $sum = 0;

        for ($i = 0; $i < $samples; $i++) {
            $sum += $uniform->random();
        }

        $mean = $sum / $samples;
        $expectedMean = (1 + 6) / 2.0; // 3.5
        $this->assertEqualsWithDelta($expectedMean, $mean, 0.1);
    }
}

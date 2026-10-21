<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests\Distributions\Continuous;

use Ignescent\LoadedDice\Distributions\Continuous\Distribution as ContinuousDistribution;
use Ignescent\LoadedDice\Distributions\Continuous\Uniform;
use Ignescent\LoadedDice\Distributions\Distribution;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

class UniformTest extends TestCase
{
    public function testImplementsDistributionInterfaces(): void
    {
        $uniform = new Uniform(new Randomizer(), 0.0, 1.0);

        $this->assertInstanceOf(Distribution::class, $uniform);
        $this->assertInstanceOf(ContinuousDistribution::class, $uniform);
    }

    public function testConstructorSetsProperties(): void
    {
        $uniform = new Uniform(new Randomizer(), 2.5, 7.5);

        $this->assertSame(2.5, $uniform->min);
        $this->assertSame(7.5, $uniform->max);
    }

    public function testRandomReturnsFloatWithinBounds(): void
    {
        $uniform = new Uniform(new Randomizer(new Mt19937(42)), 10.0, 20.0);

        for ($i = 0; $i < 50; $i++) {
            $value = $uniform->random();
            $this->assertIsFloat($value);
            $this->assertGreaterThanOrEqual(10.0, $value);
            $this->assertLessThanOrEqual(20.0, $value);
        }
    }

    public function testDeterministicWithSameSeed(): void
    {
        $uniform1 = new Uniform(new Randomizer(new Mt19937(12345)), 0.0, 100.0);
        $uniform2 = new Uniform(new Randomizer(new Mt19937(12345)), 0.0, 100.0);

        for ($i = 0; $i < 20; $i++) {
            $this->assertSame($uniform1->random(), $uniform2->random());
        }
    }

    public function testDistributionMeanApproximation(): void
    {
        $uniform = new Uniform(new Randomizer(new Mt19937(42)), 10.0, 30.0);
        $samples = 1000;
        $sum = 0.0;

        for ($i = 0; $i < $samples; $i++) {
            $sum += $uniform->random();
        }

        $mean = $sum / $samples;
        $this->assertEqualsWithDelta(20.0, $mean, 0.5);
    }
}

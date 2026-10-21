<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests\Distributions\Continuous;

use Ignescent\LoadedDice\Distributions\Continuous\Distribution as ContinuousDistribution;
use Ignescent\LoadedDice\Distributions\Continuous\Exponential;
use Ignescent\LoadedDice\Distributions\Distribution;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

class ExponentialTest extends TestCase
{
    public function testImplementsDistributionInterfaces(): void
    {
        $exponential = new Exponential(new Randomizer(), 1.5);

        $this->assertInstanceOf(Distribution::class, $exponential);
        $this->assertInstanceOf(ContinuousDistribution::class, $exponential);
    }

    public function testConstructorSetsProperties(): void
    {
        $exponential = new Exponential(new Randomizer(), 2.5);

        $this->assertSame(2.5, $exponential->lambda);
    }

    public function testRandomReturnsPositiveFloat(): void
    {
        $exponential = new Exponential(new Randomizer(new Mt19937(42)), 1.5);

        for ($i = 0; $i < 50; $i++) {
            $value = $exponential->random();
            $this->assertIsFloat($value);
            $this->assertGreaterThanOrEqual(0.0, $value);
        }
    }

    public function testDeterministicWithSameSeed(): void
    {
        $exponential1 = new Exponential(new Randomizer(new Mt19937(12345)), 0.5);
        $exponential2 = new Exponential(new Randomizer(new Mt19937(12345)), 0.5);

        for ($i = 0; $i < 20; $i++) {
            $this->assertSame($exponential1->random(), $exponential2->random());
        }
    }

    public function testDistributionMeanApproximation(): void
    {
        $lambda = 2.0;
        $exponential = new Exponential(new Randomizer(new Mt19937(42)), $lambda);
        $samples = 5000;
        $sum = 0.0;

        for ($i = 0; $i < $samples; $i++) {
            $sum += $exponential->random();
        }

        $mean = $sum / $samples;
        $expectedMean = 1.0 / $lambda; // 0.5
        $this->assertEqualsWithDelta($expectedMean, $mean, 0.05);
    }
}

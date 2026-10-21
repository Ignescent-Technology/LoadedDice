<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests\Distributions\Continuous;

use Ignescent\LoadedDice\Distributions\Continuous\Distribution as ContinuousDistribution;
use Ignescent\LoadedDice\Distributions\Continuous\Normal;
use Ignescent\LoadedDice\Distributions\Distribution;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

class NormalTest extends TestCase
{
    public function testImplementsDistributionInterfaces(): void
    {
        $normal = new Normal(new Randomizer());

        $this->assertInstanceOf(Distribution::class, $normal);
        $this->assertInstanceOf(ContinuousDistribution::class, $normal);
    }

    public function testConstructorDefaultProperties(): void
    {
        $normal = new Normal(new Randomizer());

        $this->assertSame(0.0, $normal->mean);
        $this->assertSame(1.0, $normal->standardDeviation);
    }

    public function testConstructorCustomProperties(): void
    {
        $normal = new Normal(new Randomizer(), 5.0, 2.5);

        $this->assertSame(5.0, $normal->mean);
        $this->assertSame(2.5, $normal->standardDeviation);
    }

    public function testRandomReturnsFloat(): void
    {
        $normal = new Normal(new Randomizer(new Mt19937(42)), 10.0, 2.0);

        for ($i = 0; $i < 50; $i++) {
            $value = $normal->random();
            $this->assertIsFloat($value);
        }
    }

    public function testDeterministicWithSameSeed(): void
    {
        $normal1 = new Normal(new Randomizer(new Mt19937(12345)), 10.0, 2.0);
        $normal2 = new Normal(new Randomizer(new Mt19937(12345)), 10.0, 2.0);

        for ($i = 0; $i < 20; $i++) {
            $this->assertSame($normal1->random(), $normal2->random());
        }
    }

    public function testDistributionMeanAndVarianceApproximation(): void
    {
        $targetMean = 10.0;
        $targetStdDev = 2.0;
        $normal = new Normal(new Randomizer(new Mt19937(42)), $targetMean, $targetStdDev);
        $samples = 5000;
        $values = [];

        for ($i = 0; $i < $samples; $i++) {
            $values[] = $normal->random();
        }

        $mean = array_sum($values) / $samples;
        $variance = array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $values)) / ($samples - 1);

        $this->assertEqualsWithDelta($targetMean, $mean, 0.1);
        $this->assertEqualsWithDelta($targetStdDev ** 2, $variance, 0.2);
    }
}

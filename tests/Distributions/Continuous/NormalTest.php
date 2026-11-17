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

    public function testCdfKnownValues(): void
    {
        $normal = new Normal(new Randomizer(), 0.0, 1.0);

        $this->assertEqualsWithDelta(0.5, $normal->cdf(0.0), 1e-7);
        $this->assertEqualsWithDelta(0.8413447, $normal->cdf(1.0), 1e-6);
        $this->assertEqualsWithDelta(0.1586553, $normal->cdf(-1.0), 1e-6);
        $this->assertEqualsWithDelta(0.9772499, $normal->cdf(2.0), 1e-6);
        $this->assertEqualsWithDelta(0.0227501, $normal->cdf(-2.0), 1e-6);

        // Non-standard normal distribution
        $nonStandard = new Normal(new Randomizer(), 10.0, 2.5);
        $this->assertEqualsWithDelta(0.5, $nonStandard->cdf(10.0), 1e-7);
        $this->assertEqualsWithDelta(0.8413447, $nonStandard->cdf(12.5), 1e-6);
        $this->assertEqualsWithDelta(0.1586553, $nonStandard->cdf(7.5), 1e-6);
    }

    public function testCdfSymmetry(): void
    {
        $mean = 5.0;
        $stdDev = 1.8;
        $normal = new Normal(new Randomizer(), $mean, $stdDev);

        $offsets = [0.2, 0.5, 1.0, 1.8, 3.6];
        foreach ($offsets as $offset) {
            $cdfAbove = $normal->cdf($mean + $offset);
            $cdfBelow = $normal->cdf($mean - $offset);
            $this->assertEqualsWithDelta(1.0, $cdfAbove + $cdfBelow, 1e-7);
        }
    }

    public function testPdfKnownValues(): void
    {
        $stdNormal = new Normal(new Randomizer(), 0.0, 1.0);

        $invSqrt2Pi = 1.0 / sqrt(2 * M_PI);
        $this->assertEqualsWithDelta($invSqrt2Pi, $stdNormal->pdf(0.0), 1e-10);
        $this->assertEqualsWithDelta($invSqrt2Pi * exp(-0.5), $stdNormal->pdf(1.0), 1e-10);
        $this->assertEqualsWithDelta($invSqrt2Pi * exp(-0.5), $stdNormal->pdf(-1.0), 1e-10);
        $this->assertEqualsWithDelta($invSqrt2Pi * exp(-2.0), $stdNormal->pdf(2.0), 1e-10);
        $this->assertEqualsWithDelta($invSqrt2Pi * exp(-2.0), $stdNormal->pdf(-2.0), 1e-10);

        // Non-standard normal
        $mean = 4.0;
        $stdDev = 2.0;
        $normal = new Normal(new Randomizer(), $mean, $stdDev);
        $expectedPeak = 1.0 / ($stdDev * sqrt(2 * M_PI));
        $this->assertEqualsWithDelta($expectedPeak, $normal->pdf(4.0), 1e-10);
        $this->assertEqualsWithDelta($expectedPeak * exp(-0.5), $normal->pdf(6.0), 1e-10);
        $this->assertEqualsWithDelta($expectedPeak * exp(-0.5), $normal->pdf(2.0), 1e-10);
    }

    public function testPdfSymmetry(): void
    {
        $mean = 7.0;
        $stdDev = 3.0;
        $normal = new Normal(new Randomizer(), $mean, $stdDev);

        $offsets = [0.1, 0.5, 1.5, 3.0, 6.0];
        foreach ($offsets as $offset) {
            $this->assertEqualsWithDelta(
                $normal->pdf($mean + $offset),
                $normal->pdf($mean - $offset),
                1e-10
            );
        }
    }
}

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

    public function testCdfKnownValues(): void
    {
        $lambda = 1.5;
        $exponential = new Exponential(new Randomizer(), $lambda);

        $this->assertEqualsWithDelta(0.0, $exponential->cdf(0.0), 1e-10);
        $this->assertEqualsWithDelta(1.0 - exp(-1.0), $exponential->cdf(1.0 / $lambda), 1e-10);
        $this->assertEqualsWithDelta(0.5, $exponential->cdf(log(2.0) / $lambda), 1e-10);
        $this->assertEqualsWithDelta(1.0 - exp(-3.0), $exponential->cdf(2.0), 1e-10);
    }

    public function testCdfMonotonicity(): void
    {
        $exponential = new Exponential(new Randomizer(), 2.0);

        $prev = -1.0;
        for ($x = 0.0; $x <= 5.0; $x += 0.5) {
            $cdf = $exponential->cdf($x);
            $this->assertGreaterThan($prev, $cdf);
            $this->assertLessThanOrEqual(1.0, $cdf);
            $prev = $cdf;
        }
    }

    public function testPdfKnownValues(): void
    {
        $lambda = 2.0;
        $exponential = new Exponential(new Randomizer(), $lambda);

        $this->assertEqualsWithDelta(2.0, $exponential->pdf(0.0), 1e-10);
        $this->assertEqualsWithDelta(2.0 * exp(-1.0), $exponential->pdf(0.5), 1e-10);
        $this->assertEqualsWithDelta(2.0 * exp(-2.0), $exponential->pdf(1.0), 1e-10);
        $this->assertEqualsWithDelta(2.0 * exp(-4.0), $exponential->pdf(2.0), 1e-10);
    }

    public function testPdfMonotonicity(): void
    {
        $exponential = new Exponential(new Randomizer(), 1.0);

        $prev = INF;
        for ($x = 0.0; $x <= 5.0; $x += 0.5) {
            $pdf = $exponential->pdf($x);
            $this->assertLessThan($prev, $pdf);
            $this->assertGreaterThan(0.0, $pdf);
            $prev = $pdf;
        }
    }
}

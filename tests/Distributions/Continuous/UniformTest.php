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
        $uniform = new Uniform(0.0, 1.0);

        $this->assertInstanceOf(Distribution::class, $uniform);
        $this->assertInstanceOf(ContinuousDistribution::class, $uniform);
    }

    public function testConstructorSetsProperties(): void
    {
        $uniform = new Uniform(2.5, 7.5);

        $this->assertSame(2.5, $uniform->min);
        $this->assertSame(7.5, $uniform->max);
    }

    public function testConstructorDefaultPropertiesAndRandomizer(): void
    {
        $uniform = new Uniform();

        $this->assertSame(0.0, $uniform->min);
        $this->assertSame(1.0, $uniform->max);

        $val = $uniform->random();
        $this->assertIsFloat($val);
        $this->assertGreaterThanOrEqual(0.0, $val);
        $this->assertLessThanOrEqual(1.0, $val);
    }

    public function testRandomReturnsFloatWithinBounds(): void
    {
        $uniform = new Uniform(10.0, 20.0, new Randomizer(new Mt19937(42)));

        for ($i = 0; $i < 50; $i++) {
            $value = $uniform->random();
            $this->assertIsFloat($value);
            $this->assertGreaterThanOrEqual(10.0, $value);
            $this->assertLessThanOrEqual(20.0, $value);
        }
    }

    public function testDeterministicWithSameSeed(): void
    {
        $uniform1 = new Uniform(0.0, 100.0, new Randomizer(new Mt19937(12345)));
        $uniform2 = new Uniform(0.0, 100.0, new Randomizer(new Mt19937(12345)));

        for ($i = 0; $i < 20; $i++) {
            $this->assertSame($uniform1->random(), $uniform2->random());
        }
    }

    public function testDistributionMeanApproximation(): void
    {
        $uniform = new Uniform(10.0, 30.0, new Randomizer(new Mt19937(42)));
        $samples = 1000;
        $sum = 0.0;

        for ($i = 0; $i < $samples; $i++) {
            $sum += $uniform->random();
        }

        $mean = $sum / $samples;
        $this->assertEqualsWithDelta(20.0, $mean, 0.5);
    }

    public function testCdfKnownValues(): void
    {
        $stdUniform = new Uniform(0.0, 1.0);
        $this->assertEqualsWithDelta(0.0, $stdUniform->cdf(0.0), 1e-10);
        $this->assertEqualsWithDelta(0.25, $stdUniform->cdf(0.25), 1e-10);
        $this->assertEqualsWithDelta(0.5, $stdUniform->cdf(0.5), 1e-10);
        $this->assertEqualsWithDelta(0.75, $stdUniform->cdf(0.75), 1e-10);
        $this->assertEqualsWithDelta(1.0, $stdUniform->cdf(1.0), 1e-10);

        $customUniform = new Uniform(2.0, 10.0);
        $this->assertEqualsWithDelta(0.0, $customUniform->cdf(2.0), 1e-10);
        $this->assertEqualsWithDelta(0.25, $customUniform->cdf(4.0), 1e-10);
        $this->assertEqualsWithDelta(0.5, $customUniform->cdf(6.0), 1e-10);
        $this->assertEqualsWithDelta(0.75, $customUniform->cdf(8.0), 1e-10);
        $this->assertEqualsWithDelta(1.0, $customUniform->cdf(10.0), 1e-10);
    }

    public function testPdfKnownValues(): void
    {
        $stdUniform = new Uniform(0.0, 1.0);
        $this->assertEqualsWithDelta(1.0, $stdUniform->pdf(0.0), 1e-10);
        $this->assertEqualsWithDelta(1.0, $stdUniform->pdf(0.5), 1e-10);
        $this->assertEqualsWithDelta(1.0, $stdUniform->pdf(1.0), 1e-10);

        $customUniform = new Uniform(2.0, 10.0);
        $expectedPdf = 1.0 / 8.0; // 0.125
        $this->assertEqualsWithDelta($expectedPdf, $customUniform->pdf(2.0), 1e-10);
        $this->assertEqualsWithDelta($expectedPdf, $customUniform->pdf(5.0), 1e-10);
        $this->assertEqualsWithDelta($expectedPdf, $customUniform->pdf(8.0), 1e-10);
        $this->assertEqualsWithDelta($expectedPdf, $customUniform->pdf(10.0), 1e-10);

        $negUniform = new Uniform(-5.0, 5.0);
        $this->assertEqualsWithDelta(0.1, $negUniform->pdf(0.0), 1e-10);
        $this->assertEqualsWithDelta(0.1, $negUniform->pdf(-3.0), 1e-10);
        $this->assertEqualsWithDelta(0.1, $negUniform->pdf(4.0), 1e-10);
    }
}

<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests\Distributions\Continuous;

use Ignescent\LoadedDice\Distributions\Continuous\Distribution as ContinuousDistribution;
use Ignescent\LoadedDice\Distributions\Continuous\Gamma;
use Ignescent\LoadedDice\Distributions\Distribution;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

class GammaTest extends TestCase
{
    public function testImplementsDistributionInterfaces(): void
    {
        $gamma = new Gamma(new Randomizer(), 2.0, 1.0);

        $this->assertInstanceOf(Distribution::class, $gamma);
        $this->assertInstanceOf(ContinuousDistribution::class, $gamma);
    }

    public function testConstructorSetsProperties(): void
    {
        $gamma = new Gamma(new Randomizer(), 2.5, 1.5);

        $this->assertSame(2.5, $gamma->shape);
        $this->assertSame(1.5, $gamma->scale);
    }

    public function testRandomReturnsPositiveFloat(): void
    {
        $gamma = new Gamma(new Randomizer(new Mt19937(42)), 2.0, 1.0);

        for ($i = 0; $i < 50; $i++) {
            $value = $gamma->random();
            $this->assertIsFloat($value);
            $this->assertGreaterThan(0.0, $value);
        }
    }

    public function testDeterministicWithSameSeed(): void
    {
        $gamma1 = new Gamma(new Randomizer(new Mt19937(12345)), 3.0, 1.0);
        $gamma2 = new Gamma(new Randomizer(new Mt19937(12345)), 3.0, 1.0);

        for ($i = 0; $i < 20; $i++) {
            $this->assertSame($gamma1->random(), $gamma2->random());
        }
    }

    public function testDistributionMeanApproximation(): void
    {
        $shape = 3.0;
        $gamma = new Gamma(new Randomizer(new Mt19937(42)), $shape, 1.0);
        $samples = 5000;
        $sum = 0.0;

        for ($i = 0; $i < $samples; $i++) {
            $sum += $gamma->random();
        }

        $mean = $sum / $samples;
        $this->assertEqualsWithDelta($shape, $mean, 0.1);
    }
}

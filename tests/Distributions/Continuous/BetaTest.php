<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests\Distributions\Continuous;

use Ignescent\LoadedDice\Distributions\Continuous\Beta;
use Ignescent\LoadedDice\Distributions\Continuous\Distribution as ContinuousDistribution;
use Ignescent\LoadedDice\Distributions\Distribution;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

class BetaTest extends TestCase
{
    public function testImplementsDistributionInterfaces(): void
    {
        $beta = new Beta(new Randomizer(), 2.0, 5.0);

        $this->assertInstanceOf(Distribution::class, $beta);
        $this->assertInstanceOf(ContinuousDistribution::class, $beta);
    }

    public function testConstructorSetsProperties(): void
    {
        $beta = new Beta(new Randomizer(), 2.0, 5.0);

        $this->assertSame(2.0, $beta->alpha);
        $this->assertSame(5.0, $beta->beta);
    }

    public function testRandomReturnsFloatInOpenInterval(): void
    {
        $beta = new Beta(new Randomizer(new Mt19937(42)), 2.0, 5.0);

        for ($i = 0; $i < 50; $i++) {
            $value = $beta->random();
            $this->assertIsFloat($value);
            $this->assertGreaterThan(0.0, $value);
            $this->assertLessThan(1.0, $value);
        }
    }

    public function testDeterministicWithSameSeed(): void
    {
        $beta1 = new Beta(new Randomizer(new Mt19937(12345)), 2.0, 3.0);
        $beta2 = new Beta(new Randomizer(new Mt19937(12345)), 2.0, 3.0);

        for ($i = 0; $i < 20; $i++) {
            $this->assertSame($beta1->random(), $beta2->random());
        }
    }

    public function testDistributionMeanApproximation(): void
    {
        $alpha = 2.0;
        $betaParam = 5.0;
        $beta = new Beta(new Randomizer(new Mt19937(42)), $alpha, $betaParam);
        $samples = 5000;
        $sum = 0.0;

        for ($i = 0; $i < $samples; $i++) {
            $sum += $beta->random();
        }

        $mean = $sum / $samples;
        $expectedMean = $alpha / ($alpha + $betaParam); // 2/7 ≈ 0.2857
        $this->assertEqualsWithDelta($expectedMean, $mean, 0.05);
    }
}

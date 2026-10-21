<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests\Distributions\Discrete;

use Ignescent\LoadedDice\Distributions\Discrete\Binomial;
use Ignescent\LoadedDice\Distributions\Discrete\Distribution as DiscreteDistribution;
use Ignescent\LoadedDice\Distributions\Distribution;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

class BinomialTest extends TestCase
{
    public function testImplementsDistributionInterfaces(): void
    {
        $binomial = new Binomial(new Randomizer(), 10, 0.5);

        $this->assertInstanceOf(Distribution::class, $binomial);
        $this->assertInstanceOf(DiscreteDistribution::class, $binomial);
    }

    public function testConstructorSetsProperties(): void
    {
        $binomial = new Binomial(new Randomizer(), 20, 0.7);

        $this->assertSame(20, $binomial->n);
        $this->assertSame(0.7, $binomial->p);
    }

    public function testRandomReturnsIntWithinBounds(): void
    {
        $binomial = new Binomial(new Randomizer(new Mt19937(42)), 10, 0.4);

        for ($i = 0; $i < 50; $i++) {
            $value = $binomial->random();
            $this->assertIsInt($value);
            $this->assertGreaterThanOrEqual(0, $value);
            $this->assertLessThanOrEqual(10, $value);
        }
    }

    public function testZeroProbabilityAlwaysReturnsZero(): void
    {
        $binomial = new Binomial(new Randomizer(new Mt19937(42)), 10, 0.0);

        for ($i = 0; $i < 20; $i++) {
            $this->assertSame(0, $binomial->random());
        }
    }

    public function testFullProbabilityAlwaysReturnsN(): void
    {
        $n = 15;
        $binomial = new Binomial(new Randomizer(new Mt19937(42)), $n, 1.0);

        for ($i = 0; $i < 20; $i++) {
            $this->assertSame($n, $binomial->random());
        }
    }

    public function testZeroTrialsReturnsZero(): void
    {
        $binomial = new Binomial(new Randomizer(new Mt19937(42)), 0, 0.5);

        for ($i = 0; $i < 10; $i++) {
            $this->assertSame(0, $binomial->random());
        }
    }

    public function testDeterministicWithSameSeed(): void
    {
        $binomial1 = new Binomial(new Randomizer(new Mt19937(12345)), 10, 0.3);
        $binomial2 = new Binomial(new Randomizer(new Mt19937(12345)), 10, 0.3);

        for ($i = 0; $i < 20; $i++) {
            $this->assertSame($binomial1->random(), $binomial2->random());
        }
    }

    public function testDistributionMeanApproximation(): void
    {
        $n = 20;
        $p = 0.3;
        $binomial = new Binomial(new Randomizer(new Mt19937(42)), $n, $p);
        $samples = 5000;
        $sum = 0;

        for ($i = 0; $i < $samples; $i++) {
            $sum += $binomial->random();
        }

        $mean = $sum / $samples;
        $expectedMean = $n * $p; // 6.0
        $this->assertEqualsWithDelta($expectedMean, $mean, 0.1);
    }
}

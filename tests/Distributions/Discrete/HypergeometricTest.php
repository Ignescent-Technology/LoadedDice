<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests\Distributions\Discrete;

use Ignescent\LoadedDice\Distributions\Discrete\Distribution as DiscreteDistribution;
use Ignescent\LoadedDice\Distributions\Discrete\Hypergeometric;
use Ignescent\LoadedDice\Distributions\Distribution;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

class HypergeometricTest extends TestCase
{
    public function testImplementsDistributionInterfaces(): void
    {
        $dist = new Hypergeometric(50, 10, 5);

        $this->assertInstanceOf(Distribution::class, $dist);
        $this->assertInstanceOf(DiscreteDistribution::class, $dist);
    }

    public function testConstructorSetsProperties(): void
    {
        $dist = new Hypergeometric(100, 20, 10);

        $this->assertSame(100, $dist->population);
        $this->assertSame(20, $dist->successes);
        $this->assertSame(10, $dist->draws);
    }

    public function testConstructorDefaultRandomizer(): void
    {
        $dist = new Hypergeometric(50, 10, 5);
        $val = $dist->random();

        $this->assertIsInt($val);
        $this->assertGreaterThanOrEqual(0, $val);
        $this->assertLessThanOrEqual(5, $val);
    }

    public function testRandomReturnsIntWithinBounds(): void
    {
        $dist = new Hypergeometric(52, 4, 5, new Randomizer(new Mt19937(42)));

        for ($i = 0; $i < 50; $i++) {
            $value = $dist->random();
            $this->assertIsInt($value);
            $this->assertGreaterThanOrEqual(0, $value);
            $this->assertLessThanOrEqual(4, $value);
        }
    }

    public function testZeroDrawsReturnsZero(): void
    {
        $dist = new Hypergeometric(50, 10, 0, new Randomizer(new Mt19937(42)));

        for ($i = 0; $i < 10; $i++) {
            $this->assertSame(0, $dist->random());
        }
    }

    public function testZeroSuccessesReturnsZero(): void
    {
        $dist = new Hypergeometric(50, 0, 5, new Randomizer(new Mt19937(42)));

        for ($i = 0; $i < 10; $i++) {
            $this->assertSame(0, $dist->random());
        }
    }

    public function testAllSuccessesReturnsDraws(): void
    {
        $dist = new Hypergeometric(50, 50, 10, new Randomizer(new Mt19937(42)));

        for ($i = 0; $i < 10; $i++) {
            $this->assertSame(10, $dist->random());
        }
    }

    public function testDrawAllPopulationReturnsSuccesses(): void
    {
        $dist = new Hypergeometric(50, 15, 50, new Randomizer(new Mt19937(42)));

        for ($i = 0; $i < 10; $i++) {
            $this->assertSame(15, $dist->random());
        }
    }

    public function testDeterministicWithSameSeed(): void
    {
        $dist1 = new Hypergeometric(52, 4, 5, new Randomizer(new Mt19937(12345)));
        $dist2 = new Hypergeometric(52, 4, 5, new Randomizer(new Mt19937(12345)));

        for ($i = 0; $i < 20; $i++) {
            $this->assertSame($dist1->random(), $dist2->random());
        }
    }

    public function testDistributionMeanApproximation(): void
    {
        // N = 50, K = 20, n = 10 -> Expected Mean = 10 * (20 / 50) = 4.0
        $dist = new Hypergeometric(50, 20, 10, new Randomizer(new Mt19937(42)));
        $samples = 5000;
        $sum = 0;

        for ($i = 0; $i < $samples; $i++) {
            $sum += $dist->random();
        }

        $mean = $sum / $samples;
        $this->assertEqualsWithDelta(4.0, $mean, 0.1);
    }

    public function testPmfKnownCardDeckValues(): void
    {
        // Drawing 5 cards from a 52-card deck containing 4 aces (N = 52, K = 4, n = 5)
        $dist = new Hypergeometric(52, 4, 5);

        // Exact analytical probabilities:
        // P(0) = C(4,0)*C(48,5)/C(52,5) = 1712304 / 2598960 ~= 0.6588419983
        // P(1) = C(4,1)*C(48,4)/C(52,5) = 778320 / 2598960  ~= 0.2994736356
        // P(2) = C(4,2)*C(48,3)/C(52,5) = 103776 / 2598960  ~= 0.0399298181
        // P(3) = C(4,3)*C(48,2)/C(52,5) = 4512 / 2598960    ~= 0.0017360790
        // P(4) = C(4,4)*C(48,1)/C(52,5) = 48 / 2598960      ~= 0.0000184689
        $this->assertEqualsWithDelta(1712304 / 2598960, $dist->pmf(0), 1e-10);
        $this->assertEqualsWithDelta(778320 / 2598960, $dist->pmf(1), 1e-10);
        $this->assertEqualsWithDelta(103776 / 2598960, $dist->pmf(2), 1e-10);
        $this->assertEqualsWithDelta(4512 / 2598960, $dist->pmf(3), 1e-10);
        $this->assertEqualsWithDelta(48 / 2598960, $dist->pmf(4), 1e-10);
        $this->assertSame(0.0, $dist->pmf(5));
    }

    public function testPmfKnownMarbleUrnValues(): void
    {
        // N = 10, K = 6, n = 4. Total combinations C(10,4) = 210.
        $dist = new Hypergeometric(10, 6, 4);

        $this->assertEqualsWithDelta(1 / 210, $dist->pmf(0), 1e-10);
        $this->assertEqualsWithDelta(24 / 210, $dist->pmf(1), 1e-10);
        $this->assertEqualsWithDelta(90 / 210, $dist->pmf(2), 1e-10);
        $this->assertEqualsWithDelta(80 / 210, $dist->pmf(3), 1e-10);
        $this->assertEqualsWithDelta(15 / 210, $dist->pmf(4), 1e-10);
    }

    public function testPmfSupportBoundaries(): void
    {
        $dist = new Hypergeometric(52, 4, 5);

        $this->assertSame(0.0, $dist->pmf(-1));
        $this->assertSame(0.0, $dist->pmf(5));
        $this->assertSame(0.0, $dist->pmf(10));
    }

    public function testPmfTotalSumEqualsOne(): void
    {
        $dist = new Hypergeometric(52, 4, 5);

        $total = 0.0;
        for ($k = 0; $k <= 4; $k++) {
            $total += $dist->pmf($k);
        }

        $this->assertEqualsWithDelta(1.0, $total, 1e-10);
    }

    public function testNonZeroMinimumSupport(): void
    {
        // N = 10, K = 8, n = 5 -> N - K = 2 failures, so at least 5 - 2 = 3 successes guaranteed.
        $dist = new Hypergeometric(10, 8, 5);

        $this->assertSame(0.0, $dist->pmf(0));
        $this->assertSame(0.0, $dist->pmf(1));
        $this->assertSame(0.0, $dist->pmf(2));

        // Total C(10,5) = 252
        // k=3: C(8,3)*C(2,2) = 56 * 1 = 56 / 252
        // k=4: C(8,4)*C(2,1) = 70 * 2 = 140 / 252
        // k=5: C(8,5)*C(2,0) = 56 * 1 = 56 / 252
        $this->assertEqualsWithDelta(56 / 252, $dist->pmf(3), 1e-10);
        $this->assertEqualsWithDelta(140 / 252, $dist->pmf(4), 1e-10);
        $this->assertEqualsWithDelta(56 / 252, $dist->pmf(5), 1e-10);

        // CDF tests
        $this->assertSame(0.0, $dist->cdf(2));
        $this->assertEqualsWithDelta(56 / 252, $dist->cdf(3), 1e-10);
        $this->assertEqualsWithDelta(196 / 252, $dist->cdf(4), 1e-10);
        $this->assertSame(1.0, $dist->cdf(5));
    }

    public function testCdfKnownValues(): void
    {
        $dist = new Hypergeometric(52, 4, 5);

        $p0 = 1712304 / 2598960;
        $p1 = 778320 / 2598960;
        $p2 = 103776 / 2598960;
        $p3 = 4512 / 2598960;
        $p4 = 48 / 2598960;

        $this->assertEqualsWithDelta($p0, $dist->cdf(0), 1e-10);
        $this->assertEqualsWithDelta($p0 + $p1, $dist->cdf(1), 1e-10);
        $this->assertEqualsWithDelta($p0 + $p1 + $p2, $dist->cdf(2), 1e-10);
        $this->assertEqualsWithDelta($p0 + $p1 + $p2 + $p3, $dist->cdf(3), 1e-10);
        $this->assertEqualsWithDelta(1.0, $dist->cdf(4), 1e-10);
    }

    public function testCdfBoundaries(): void
    {
        $dist = new Hypergeometric(52, 4, 5);

        $this->assertSame(0.0, $dist->cdf(-1));
        $this->assertSame(1.0, $dist->cdf(4));
        $this->assertSame(1.0, $dist->cdf(5));
        $this->assertSame(1.0, $dist->cdf(100));
    }

    public function testCdfMonotonicity(): void
    {
        $dist = new Hypergeometric(52, 4, 5);

        $prevCdf = 0.0;
        for ($k = -2; $k <= 10; $k++) {
            $currentCdf = $dist->cdf($k);
            $this->assertGreaterThanOrEqual($prevCdf, $currentCdf);
            $this->assertGreaterThanOrEqual(0.0, $currentCdf);
            $this->assertLessThanOrEqual(1.0, $currentCdf);
            $prevCdf = $currentCdf;
        }
    }

    public function testInvalidParameters(): void
    {
        $dist = new Hypergeometric(10, 15, 5); // successes > population

        $this->assertSame(0.0, $dist->pmf(0));
        $this->assertSame(0.0, $dist->cdf(0));
    }
}

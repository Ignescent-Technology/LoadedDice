<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests;

use Ignescent\LoadedDice\Distributions\Continuous\Beta as ContinuousBeta;
use Ignescent\LoadedDice\Distributions\Continuous\Exponential as ContinuousExponential;
use Ignescent\LoadedDice\Distributions\Continuous\Gamma as ContinuousGamma;
use Ignescent\LoadedDice\Distributions\Continuous\Normal as ContinuousNormal;
use Ignescent\LoadedDice\Distributions\Continuous\Uniform as ContinuousUniform;
use Ignescent\LoadedDice\Distributions\Discrete\Binomial as DiscreteBinomial;
use Ignescent\LoadedDice\Distributions\Discrete\Geometric as DiscreteGeometric;
use Ignescent\LoadedDice\Distributions\Discrete\Poisson as DiscretePoisson;
use Ignescent\LoadedDice\Distributions\Discrete\Uniform as DiscreteUniform;
use Ignescent\LoadedDice\Facade;
use Ignescent\LoadedDice\Hashing\Hmac;
use PHPUnit\Framework\TestCase;
use Stringable;

class FacadeTest extends TestCase
{
    private Facade $facade;

    protected function setUp(): void
    {
        $hash = new Hmac('facade-test-key', 'sha256');
        $this->facade = new Facade($hash);
    }

    public function testBooleanReturnsDeterministicBool(): void
    {
        $result1 = $this->facade->boolean('user-flag');
        $result2 = $this->facade->boolean('user-flag');

        $this->assertSame($result1, $result2);
        $this->assertIsBool($result1);
    }

    public function testFloatReturnsDeterministicFloatInRange(): void
    {
        $val1 = $this->facade->float('sample-input');
        $val2 = $this->facade->float('sample-input');

        $this->assertSame($val1, $val2);
        $this->assertGreaterThanOrEqual(0.0, $val1);
        $this->assertLessThan(1.0, $val1);
    }

    public function testIntegerReturnsDeterministicIntegerInRange(): void
    {
        $int1 = $this->facade->integer('sample-input', 100);
        $int2 = $this->facade->integer('sample-input', 100);

        $this->assertSame($int1, $int2);
        $this->assertGreaterThanOrEqual(0, $int1);
        $this->assertLessThanOrEqual(100, $int1);

        $defaultMax = $this->facade->integer('sample-input');
        $this->assertGreaterThanOrEqual(0, $defaultMax);
        $this->assertLessThanOrEqual(PHP_INT_MAX, $defaultMax);
    }

    public function testWeightedRendezvous(): void
    {
        $sites = [
            'site-a' => 1.0,
            'site-b' => 5.0,
            'site-c' => 2.0,
        ];

        $res1 = Facade::weightedRendezvous('object-1', $sites, 2);
        $res2 = Facade::weightedRendezvous('object-1', $sites, 2);

        $this->assertSame($res1, $res2);
        $this->assertCount(2, $res1);
        $this->assertContains($res1[0], array_keys($sites));
        $this->assertContains($res1[1], array_keys($sites));
    }

    public function testRandomElementReturnsNullForEmptyCollection(): void
    {
        $this->assertNull($this->facade->randomElement([]));
    }

    public function testRandomElementReturnsSingleItem(): void
    {
        $this->assertSame('only', $this->facade->randomElement(['only']));
    }

    public function testRandomElementReturnsDeterministicElement(): void
    {
        $collection = ['option-a', 'option-b', 'option-c'];

        $res1 = $this->facade->randomElement($collection);
        $res2 = $this->facade->randomElement($collection);

        $this->assertSame($res1, $res2);
        $this->assertContains($res1, $collection);
    }

    public function testRandomSubsetReturnsRequestedCount(): void
    {
        $collection = ['one', 'two', 'three', 'four', 'five'];

        $subset = $this->facade->randomSubset(3, $collection);

        $this->assertCount(3, $subset);
        $this->assertTrue(array_is_list($subset));
    }

    public function testRandomSubsetPreserveKeys(): void
    {
        $collection = ['a' => 'alpha', 'b' => 'beta', 'c' => 'gamma'];

        $subset = $this->facade->randomSubset(2, $collection, true);

        $this->assertCount(2, $subset);
        foreach ($subset as $key => $value) {
            $this->assertArrayHasKey($key, $collection);
            $this->assertSame($collection[$key], $value);
        }
    }

    public function testIdenticalKeysProduceIdenticalResults(): void
    {
        $facade1 = new Facade(new Hmac('same-key'));
        $facade2 = new Facade(new Hmac('same-key'));

        $this->assertSame($facade1->boolean('roll'), $facade2->boolean('roll'));
        $this->assertSame($facade1->float('roll'), $facade2->float('roll'));
        $this->assertSame(
            $facade1->randomElement(['a', 'b', 'c']),
            $facade2->randomElement(['a', 'b', 'c'])
        );
        $this->assertSame(
            $facade1->randomSubset(2, ['a', 'b', 'c', 'd']),
            $facade2->randomSubset(2, ['a', 'b', 'c', 'd'])
        );
    }

    public function testDifferentKeysProduceDifferentResults(): void
    {
        $facade1 = new Facade(new Hmac('key-alpha'));
        $facade2 = new Facade(new Hmac('key-beta'));

        // Float values should differ with different keys
        $this->assertNotSame($facade1->float('test-item'), $facade2->float('test-item'));
    }

    public function testStringableSupport(): void
    {
        $str1 = new class implements Stringable {
            public function __toString(): string
            {
                return 'item-1';
            }
        };
        $str2 = new class implements Stringable {
            public function __toString(): string
            {
                return 'item-2';
            }
        };

        $this->assertIsBool($this->facade->boolean($str1));
        $this->assertIsFloat($this->facade->float($str1));
        $this->assertIsInt($this->facade->integer($str1, 50));
        $this->assertNotNull($this->facade->randomElement([$str1, $str2]));
        $this->assertCount(2, $this->facade->randomSubset(2, [$str1, $str2]));
    }

    public function testBetaGeneratorReturnsBetaDistributionInstance(): void
    {
        $gen = $this->facade->betaGenerator('beta-seed', 2.0, 5.0);

        $this->assertInstanceOf(ContinuousBeta::class, $gen);
        $sample1 = $gen->random();
        $sample2 = $this->facade->betaGenerator('beta-seed', 2.0, 5.0)->random();

        $this->assertSame($sample1, $sample2);
        $this->assertGreaterThanOrEqual(0.0, $sample1);
        $this->assertLessThanOrEqual(1.0, $sample1);
    }

    public function testExponentialGeneratorReturnsExponentialDistributionInstance(): void
    {
        $gen = $this->facade->exponentialGenerator('exp-seed', 1.5);

        $this->assertInstanceOf(ContinuousExponential::class, $gen);
        $sample1 = $gen->random();
        $sample2 = $this->facade->exponentialGenerator('exp-seed', 1.5)->random();

        $this->assertSame($sample1, $sample2);
        $this->assertGreaterThan(0.0, $sample1);
    }

    public function testGammaGeneratorReturnsGammaDistributionInstance(): void
    {
        $gen = $this->facade->gammaGenerator('gamma-seed', 2.0, 2.0);

        $this->assertInstanceOf(ContinuousGamma::class, $gen);
        $sample1 = $gen->random();
        $sample2 = $this->facade->gammaGenerator('gamma-seed', 2.0, 2.0)->random();

        $this->assertSame($sample1, $sample2);
        $this->assertGreaterThan(0.0, $sample1);
    }

    public function testNormalGeneratorReturnsNormalDistributionInstance(): void
    {
        $gen = $this->facade->normalGenerator('normal-seed', 10.0, 2.0);

        $this->assertInstanceOf(ContinuousNormal::class, $gen);
        $sample1 = $gen->random();
        $sample2 = $this->facade->normalGenerator('normal-seed', 10.0, 2.0)->random();

        $this->assertSame($sample1, $sample2);
        $this->assertIsFloat($sample1);
    }

    public function testContinuousUniformGeneratorReturnsContinuousUniformDistributionInstance(): void
    {
        $gen = $this->facade->continuousUniformGenerator('cuniform-seed', 5.0, 10.0);

        $this->assertInstanceOf(ContinuousUniform::class, $gen);
        $sample1 = $gen->random();
        $sample2 = $this->facade->continuousUniformGenerator('cuniform-seed', 5.0, 10.0)->random();

        $this->assertSame($sample1, $sample2);
        $this->assertGreaterThanOrEqual(5.0, $sample1);
        $this->assertLessThanOrEqual(10.0, $sample1);
    }

    public function testBinomialGeneratorReturnsBinomialDistributionInstance(): void
    {
        $gen = $this->facade->binomialGenerator('binom-seed', 10, 0.5);

        $this->assertInstanceOf(DiscreteBinomial::class, $gen);
        $sample1 = $gen->random();
        $sample2 = $this->facade->binomialGenerator('binom-seed', 10, 0.5)->random();

        $this->assertSame($sample1, $sample2);
        $this->assertGreaterThanOrEqual(0, $sample1);
        $this->assertLessThanOrEqual(10, $sample1);
    }

    public function testGeometricGeneratorReturnsGeometricDistributionInstance(): void
    {
        $gen = $this->facade->geometricGenerator('geom-seed', 0.3);

        $this->assertInstanceOf(DiscreteGeometric::class, $gen);
        $sample1 = $gen->random();
        $sample2 = $this->facade->geometricGenerator('geom-seed', 0.3)->random();

        $this->assertSame($sample1, $sample2);
        $this->assertGreaterThanOrEqual(0, $sample1);
    }

    public function testPoissonGeneratorReturnsPoissonDistributionInstance(): void
    {
        $gen = $this->facade->poissonGenerator('poisson-seed', 4.0);

        $this->assertInstanceOf(DiscretePoisson::class, $gen);
        $sample1 = $gen->random();
        $sample2 = $this->facade->poissonGenerator('poisson-seed', 4.0)->random();

        $this->assertSame($sample1, $sample2);
        $this->assertGreaterThanOrEqual(0, $sample1);
    }

    public function testDiscreteUniformGeneratorReturnsDiscreteUniformDistributionInstance(): void
    {
        $gen = $this->facade->discreteUniformGenerator('duniform-seed', 1, 6);

        $this->assertInstanceOf(DiscreteUniform::class, $gen);
        $sample1 = $gen->random();
        $sample2 = $this->facade->discreteUniformGenerator('duniform-seed', 1, 6)->random();

        $this->assertSame($sample1, $sample2);
        $this->assertGreaterThanOrEqual(1, $sample1);
        $this->assertLessThanOrEqual(6, $sample1);
    }
}

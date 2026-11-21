<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests;

use Ignescent\LoadedDice\Bandits\Bandit;
use Ignescent\LoadedDice\Bandits\ExperimentalData;
use Ignescent\LoadedDice\Facade;
use Ignescent\LoadedDice\Hashing\Hmac;
use PHPUnit\Framework\TestCase;
use Random\Randomizer;
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

    public function testThompsonReturnsBanditInstance(): void
    {
        $data = new ExperimentalData(['arm_a' => [], 'arm_b' => []]);
        $bandit = $this->facade->thompson($data, 'bandit-seed');

        $this->assertInstanceOf(Bandit::class, $bandit);
        $arm = $bandit->pull();
        $this->assertContains($arm, ['arm_a', 'arm_b']);

        // Default seed (omitted $value)
        $banditDefault = $this->facade->thompson($data);
        $this->assertInstanceOf(Bandit::class, $banditDefault);
        $this->assertContains($banditDefault->pull(), ['arm_a', 'arm_b']);
    }

    public function testSeedRandomizer(): void
    {
        $r1 = $this->facade->seedRandomizer('seed-1');
        $r2 = $this->facade->seedRandomizer('seed-1');
        $this->assertInstanceOf(Randomizer::class, $r1);
        $this->assertSame($r1->getInt(0, 1000), $r2->getInt(0, 1000));

        $rDefault = $this->facade->seedRandomizer();
        $this->assertInstanceOf(Randomizer::class, $rDefault);

        $seedObject = new class implements Stringable {
            public function __toString(): string
            {
                return 'seed-1';
            }
        };
        $rStr = $this->facade->seedRandomizer('seed-1');
        $rObj = $this->facade->seedRandomizer($seedObject);
        $this->assertSame($rStr->getInt(0, 1000), $rObj->getInt(0, 1000));
    }

    public function testThompsonWithStringable(): void
    {
        $seedObject = new class implements Stringable {
            public function __toString(): string
            {
                return 'stringable-seed';
            }
        };

        $data = new ExperimentalData(['arm1' => [], 'arm2' => []]);
        $bandit1 = $this->facade->thompson($data, 'stringable-seed');
        $bandit2 = $this->facade->thompson($data, $seedObject);
        $this->assertSame($bandit1->pull(), $bandit2->pull());
    }
}

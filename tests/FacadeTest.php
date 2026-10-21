<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests;

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
        $this->assertNotNull($this->facade->randomElement([$str1, $str2]));
        $this->assertCount(2, $this->facade->randomSubset(2, [$str1, $str2]));
    }
}

<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests\Laravel;

use Ignescent\LoadedDice\Facade;
use Ignescent\LoadedDice\Hashing\Hmac;
use Ignescent\LoadedDice\Laravel\LoadedDice;
use Ignescent\LoadedDice\Laravel\LoadedDiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Stringable;

class FauxRandomTest extends TestCase
{
    protected function setUp(): void
    {
        $app = $this->createStub(Application::class);
        $app->method('runningInConsole')->willReturn(false);

        $provider = new LoadedDiceProvider($app);
        $provider->boot();

        LoadedDice::swap(new Facade(new Hmac('test-laravel-key')));
    }

    public function testMacroIsRegistered(): void
    {
        $this->assertTrue(Collection::hasMacro('fauxRandom'));
    }

    public function testFauxRandomWithNullReturnsSingleElement(): void
    {
        $collection = new Collection(['apple', 'banana', 'cherry']);

        $element = $collection->fauxRandom();

        $this->assertIsString($element);
        $this->assertContains($element, ['apple', 'banana', 'cherry']);
    }

    public function testFauxRandomWithNullOnEmptyCollectionReturnsNull(): void
    {
        $collection = new Collection([]);

        $this->assertNull($collection->fauxRandom());
    }

    public function testFauxRandomWithNullOnAssociativeCollectionReturnsValue(): void
    {
        $collection = new Collection([
            'first' => 'alpha',
            'second' => 'beta',
            'third' => 'gamma',
        ]);

        $element = $collection->fauxRandom();

        $this->assertIsString($element);
        $this->assertContains($element, ['alpha', 'beta', 'gamma']);
    }

    public function testFauxRandomWithIntegerReturnsNewCollectionOfRequestedSize(): void
    {
        $collection = new Collection(['a', 'b', 'c', 'd', 'e']);

        $result = $collection->fauxRandom(3);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(3, $result);
        $this->assertTrue(array_is_list($result->all()));
        foreach ($result as $item) {
            $this->assertContains($item, $collection->all());
        }
    }

    public function testFauxRandomWithZeroReturnsEmptyCollection(): void
    {
        $collection = new Collection(['a', 'b', 'c']);

        $result = $collection->fauxRandom(0);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function testFauxRandomWithCountExceedingSizeReturnsAllElements(): void
    {
        $collection = new Collection(['a', 'b']);

        $result = $collection->fauxRandom(10);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    public function testFauxRandomWithCallableEvaluatesCallback(): void
    {
        $collection = new Collection(['a', 'b', 'c', 'd', 'e']);

        $callbackCalled = false;
        $result = $collection->fauxRandom(function (Collection $col) use (&$callbackCalled, $collection): int {
            $callbackCalled = true;
            $this->assertSame($collection, $col);
            return 2;
        });

        $this->assertTrue($callbackCalled);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    public function testFauxRandomPreserveKeysFalseResetsKeys(): void
    {
        $collection = new Collection([
            'k1' => 'v1',
            'k2' => 'v2',
            'k3' => 'v3',
        ]);

        $result = $collection->fauxRandom(2, false);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertTrue(array_is_list($result->all()));
    }

    public function testFauxRandomPreserveKeysTrueKeepsOriginalKeys(): void
    {
        $collection = new Collection([
            'k1' => 'v1',
            'k2' => 'v2',
            'k3' => 'v3',
        ]);

        $result = $collection->fauxRandom(2, true);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        foreach ($result as $key => $value) {
            $this->assertArrayHasKey($key, $collection->all());
            $this->assertSame($collection[$key], $value);
        }
    }

    public function testFauxRandomIsDeterministicWithSameKey(): void
    {
        $collection = new Collection(['first', 'second', 'third', 'fourth', 'fifth']);

        LoadedDice::swap(new Facade(new Hmac('deterministic-key')));
        $res1 = $collection->fauxRandom();
        $subset1 = $collection->fauxRandom(3)->all();

        LoadedDice::swap(new Facade(new Hmac('deterministic-key')));
        $res2 = $collection->fauxRandom();
        $subset2 = $collection->fauxRandom(3)->all();

        $this->assertSame($res1, $res2);
        $this->assertSame($subset1, $subset2);
    }

    public function testFauxRandomProducesDifferentResultsWithDifferentKeys(): void
    {
        $collection = new Collection(['alpha', 'beta', 'gamma', 'delta', 'epsilon', 'zeta', 'eta', 'theta']);

        LoadedDice::swap(new Facade(new Hmac('key-A')));
        $subsetA = $collection->fauxRandom(4)->all();

        LoadedDice::swap(new Facade(new Hmac('key-B')));
        $subsetB = $collection->fauxRandom(4)->all();

        $this->assertNotSame($subsetA, $subsetB);
    }

    public function testFauxRandomWithStringableElements(): void
    {
        $s1 = new class implements Stringable {
            public function __toString(): string
            {
                return 'item-1';
            }
        };
        $s2 = new class implements Stringable {
            public function __toString(): string
            {
                return 'item-2';
            }
        };

        $collection = new Collection([$s1, $s2]);

        $single = $collection->fauxRandom();
        $this->assertNotNull($single);
        $this->assertInstanceOf(Stringable::class, $single);

        $subset = $collection->fauxRandom(2);
        $this->assertCount(2, $subset);
    }
}

<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests\Laravel;

use Ignescent\LoadedDice\Laravel\LoadedDiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Stringable;

class RendezvousTest extends TestCase
{
    protected function setUp(): void
    {
        $app = $this->createStub(Application::class);
        $app->method('runningInConsole')->willReturn(false);

        $provider = new LoadedDiceProvider($app);
        $provider->boot();
    }

    public function testMacroIsRegistered(): void
    {
        $this->assertTrue(Collection::hasMacro('rendezvous'));
    }

    public function testRendezvousWithNullReturnsSingleElement(): void
    {
        $collection = new Collection(['server-1', 'server-2', 'server-3']);

        $element = $collection->rendezvous('session:user_123');

        $this->assertIsString($element);
        $this->assertContains($element, ['server-1', 'server-2', 'server-3']);
    }

    public function testRendezvousWithNullOnEmptyCollectionReturnsNull(): void
    {
        $collection = new Collection([]);

        $this->assertNull($collection->rendezvous('session:user_123'));
    }

    public function testRendezvousWithNullOnAssociativeCollectionReturnsValue(): void
    {
        $collection = new Collection([
            'primary' => 'server-1',
            'secondary' => 'server-2',
            'tertiary' => 'server-3',
        ]);

        $element = $collection->rendezvous('item-xyz');

        $this->assertIsString($element);
        $this->assertContains($element, ['server-1', 'server-2', 'server-3']);
    }

    public function testRendezvousWithIntegerReturnsNewCollectionOfRequestedSize(): void
    {
        $collection = new Collection(['node-1', 'node-2', 'node-3', 'node-4', 'node-5']);

        $result = $collection->rendezvous('key-abc', null, 3);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(3, $result);
        $this->assertTrue(array_is_list($result->all()));
        foreach ($result as $item) {
            $this->assertContains($item, $collection->all());
        }
    }

    public function testRendezvousWithZeroReturnsEmptyCollection(): void
    {
        $collection = new Collection(['node-1', 'node-2', 'node-3']);

        $result = $collection->rendezvous('key-abc', null, 0);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function testRendezvousWithCountExceedingSizeReturnsAllElements(): void
    {
        $collection = new Collection(['node-1', 'node-2']);

        $result = $collection->rendezvous('key-abc', null, 10);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    public function testRendezvousWithCallableEvaluatesCallback(): void
    {
        $collection = new Collection(['node-1', 'node-2', 'node-3', 'node-4']);

        $callbackCalled = false;
        $result = $collection->rendezvous('key-abc', null, function (Collection $col) use (&$callbackCalled, $collection): int {
            $callbackCalled = true;
            $this->assertSame($collection, $col);
            return 2;
        });

        $this->assertTrue($callbackCalled);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    public function testRendezvousPreserveKeysFalseResetsKeys(): void
    {
        $collection = new Collection([
            'k1' => 'v1',
            'k2' => 'v2',
            'k3' => 'v3',
        ]);

        $result = $collection->rendezvous('key-abc', null, 2, false);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertTrue(array_is_list($result->all()));
    }

    public function testRendezvousPreserveKeysTrueKeepsOriginalKeys(): void
    {
        $collection = new Collection([
            'k1' => 'v1',
            'k2' => 'v2',
            'k3' => 'v3',
        ]);

        $result = $collection->rendezvous('key-abc', null, 2, true);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        foreach ($result as $key => $value) {
            $this->assertArrayHasKey($key, $collection->all());
            $this->assertSame($collection[$key], $value);
        }
    }

    public function testRendezvousIsDeterministicWithSameIdentifier(): void
    {
        $collection = new Collection(['node-1', 'node-2', 'node-3', 'node-4', 'node-5']);

        $res1 = $collection->rendezvous('cache-key-99');
        $subset1 = $collection->rendezvous('cache-key-99', null, 3)->all();

        $res2 = $collection->rendezvous('cache-key-99');
        $subset2 = $collection->rendezvous('cache-key-99', null, 3)->all();

        $this->assertSame($res1, $res2);
        $this->assertSame($subset1, $subset2);
    }

    public function testRendezvousWithStringableIdentifier(): void
    {
        $identifier = new class implements Stringable {
            public function __toString(): string
            {
                return 'stringable-object-id';
            }
        };

        $collection = new Collection(['node-1', 'node-2', 'node-3']);

        $resStringable = $collection->rendezvous($identifier);
        $resString = $collection->rendezvous('stringable-object-id');

        $this->assertSame($resString, $resStringable);
    }

    public function testRendezvousWithWeightedSites(): void
    {
        $collection = new Collection(['node-low', 'node-high']);
        $weights = [
            'node-low' => 0.00001,
            'node-high' => 10000.0,
        ];

        // Over multiple varied keys, node-high should be overwhelmingly selected
        $highCount = 0;
        for ($i = 0; $i < 50; $i++) {
            $selected = $collection->rendezvous("user_{$i}", $weights);
            if ($selected === 'node-high') {
                $highCount++;
            }
        }

        $this->assertGreaterThan(45, $highCount);
    }

    public function testRendezvousWithWeightedSitesSubset(): void
    {
        $collection = new Collection(['alpha', 'beta', 'gamma']);
        $weights = [
            'alpha' => 1.0,
            'beta' => 2.0,
            'gamma' => 3.0,
        ];

        $result = $collection->rendezvous('object-42', $weights, 2);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        foreach ($result as $item) {
            $this->assertContains($item, ['alpha', 'beta', 'gamma']);
        }
    }
}

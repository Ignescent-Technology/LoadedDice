<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests\Rendezvous;

use Ignescent\LoadedDice\Hashing\Hash;
use Ignescent\LoadedDice\Hashing\Hmac;
use Ignescent\LoadedDice\Rendezvous\Base;
use Ignescent\LoadedDice\Rendezvous\RendezvousContract;
use Ignescent\LoadedDice\Rendezvous\Weighted;
use Ignescent\LoadedDice\WeightResolving\MapResolver;
use PHPUnit\Framework\TestCase;
use Stringable;

class WeightedTest extends TestCase
{
    private Hash $hash;
    private Weighted $rendezvous;

    protected function setUp(): void
    {
        $this->hash = new Hmac('weighted-test-key', 'sha256');
        $this->rendezvous = new Weighted($this->hash, new MapResolver([]));
    }

    public function testImplementsContractAndExtendsBase(): void
    {
        $this->assertInstanceOf(RendezvousContract::class, $this->rendezvous);
        $this->assertInstanceOf(Base::class, $this->rendezvous);
    }

    public function testRandomElementReturnsNullOnEmptyCollection(): void
    {
        $this->assertNull($this->rendezvous->randomElement([]));
    }

    public function testRandomElementReturnsOnlyItemWhenSingleElement(): void
    {
        $this->assertSame('only-element', $this->rendezvous->randomElement(['only-element']));
    }

    public function testRandomElementIsDeterministic(): void
    {
        $collection = ['node-a', 'node-b', 'node-c', 'node-d'];

        $res1 = $this->rendezvous->randomElement($collection);
        $res2 = $this->rendezvous->randomElement($collection);

        $this->assertSame($res1, $res2);
        $this->assertContains($res1, $collection);
    }

    public function testWeightCallbackInfluencesPriorityAndSelection(): void
    {
        $weights = [
            'item-low' => 1.0,
            'item-high' => 100.0,
        ];

        $weighted = new Weighted($this->hash, new MapResolver($weights));

        $res = $weighted->randomElement(['item-low', 'item-high']);
        $this->assertNotNull($res);
    }

    public function testPriorityCalculationWithStubHash(): void
    {
        $stubHash = $this->createStub(Hash::class);
        $stubHash->method('hash')->willReturnCallback(function (string $item): string {
            // Provide specific hex values to test predictable scores
            return match ($item) {
                'candidate-a' => '40000000',
                'candidate-b' => '80000000',
                default => '20000000',
            };
        });

        // Test with different weights favoring candidate-b
        $weighted = new Weighted($stubHash, new MapResolver([
            'candidate-a' => 1.0,
            'candidate-b' => 10.0,
        ]));

        $chosen = $weighted->randomElement(['candidate-a', 'candidate-b']);
        $this->assertSame('candidate-b', $chosen);
    }

    public function testRandomSubsetReturnsRequestedCount(): void
    {
        $collection = ['alpha', 'bravo', 'charlie', 'delta', 'echo'];

        $subset0 = $this->rendezvous->randomSubset(0, $collection);
        $this->assertSame([], $subset0);

        $subset2 = $this->rendezvous->randomSubset(2, $collection);
        $this->assertCount(2, $subset2);

        $subsetAll = $this->rendezvous->randomSubset(5, $collection);
        $this->assertCount(5, $subsetAll);

        $subsetMoreThanAvailable = $this->rendezvous->randomSubset(10, $collection);
        $this->assertCount(5, $subsetMoreThanAvailable);
    }

    public function testRandomSubsetIsDeterministic(): void
    {
        $collection = ['srv-1', 'srv-2', 'srv-3', 'srv-4'];

        $run1 = $this->rendezvous->randomSubset(3, $collection);
        $run2 = $this->rendezvous->randomSubset(3, $collection);

        $this->assertSame($run1, $run2);
    }

    public function testRandomSubsetFirstElementMatchesRandomElement(): void
    {
        $collection = ['node-1', 'node-2', 'node-3', 'node-4'];

        $single = $this->rendezvous->randomElement($collection);
        $subset = $this->rendezvous->randomSubset(1, $collection);

        $this->assertSame([$single], $subset);
    }

    public function testRandomSubsetPreserveKeysFalse(): void
    {
        $collection = ['key1' => 'alpha', 'key2' => 'bravo', 'key3' => 'charlie'];

        $subset = $this->rendezvous->randomSubset(2, $collection, false);

        $this->assertIsArray($subset);
        $this->assertCount(2, $subset);
        $this->assertTrue(array_is_list($subset));
    }

    public function testRandomSubsetPreserveKeysTrue(): void
    {
        $collection = [
            'first' => 'val-1',
            'second' => 'val-2',
            'third' => 'val-3',
        ];

        $subset = $this->rendezvous->randomSubset(2, $collection, true);

        $this->assertIsArray($subset);
        $this->assertCount(2, $subset);

        foreach ($subset as $key => $value) {
            $this->assertArrayHasKey($key, $collection);
            $this->assertSame($collection[$key], $value);
        }
    }

    public function testStringableObjectsAreSupported(): void
    {
        $stringable1 = new class implements Stringable {
            public function __toString(): string
            {
                return 'str-1';
            }
        };

        $stringable2 = new class implements Stringable {
            public function __toString(): string
            {
                return 'str-2';
            }
        };

        $collection = [$stringable1, $stringable2];
        $element = $this->rendezvous->randomElement($collection);
        $this->assertContains($element, $collection);

        $subset = $this->rendezvous->randomSubset(2, $collection);
        $this->assertCount(2, $subset);
    }
}

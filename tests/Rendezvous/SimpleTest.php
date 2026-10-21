<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests\Rendezvous;

use Ignescent\LoadedDice\Hashing\Hash;
use Ignescent\LoadedDice\Hashing\Hmac;
use Ignescent\LoadedDice\Rendezvous\Base;
use Ignescent\LoadedDice\Rendezvous\RendezvousContract;
use Ignescent\LoadedDice\Rendezvous\Simple;
use PHPUnit\Framework\TestCase;
use Stringable;

class SimpleTest extends TestCase
{
    private Hash $hash;
    private Simple $rendezvous;

    protected function setUp(): void
    {
        $this->hash = new Hmac('rendezvous-secret-key', 'sha256');
        $this->rendezvous = new Simple($this->hash);
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
        $this->assertSame('only-option', $this->rendezvous->randomElement(['only-option']));
    }

    public function testRandomElementIsDeterministic(): void
    {
        $collection = ['apple', 'banana', 'cherry', 'date', 'elderberry'];

        $first = $this->rendezvous->randomElement($collection);
        $second = $this->rendezvous->randomElement($collection);

        $this->assertSame($first, $second);
        $this->assertContains($first, $collection);
    }

    public function testRandomElementSelectsElementWithLowestHashWeight(): void
    {
        $stubHash = $this->createStub(Hash::class);
        $stubHash->method('hash')->willReturnCallback(function (string $item): string {
            return match ($item) {
                'heavy' => 'ffff',
                'light' => '0001',
                'medium' => '0100',
                default => '0000',
            };
        });

        $simple = new Simple($stubHash);
        $selected = $simple->randomElement(['heavy', 'light', 'medium']);

        $this->assertSame('light', $selected);
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
        $collection = ['red', 'green', 'blue', 'yellow', 'purple'];

        $run1 = $this->rendezvous->randomSubset(3, $collection);
        $run2 = $this->rendezvous->randomSubset(3, $collection);

        $this->assertSame($run1, $run2);
    }

    public function testRandomSubsetFirstElementMatchesRandomElement(): void
    {
        $collection = ['server-1', 'server-2', 'server-3', 'server-4'];

        $single = $this->rendezvous->randomElement($collection);
        $subset = $this->rendezvous->randomSubset(1, $collection);

        $this->assertSame([$single], $subset);
    }

    public function testRandomSubsetPreserveKeysFalse(): void
    {
        $collection = ['k1' => 'alpha', 'k2' => 'bravo', 'k3' => 'charlie'];

        $subset = $this->rendezvous->randomSubset(2, $collection, false);

        $this->assertIsArray($subset);
        $this->assertCount(2, $subset);
        $this->assertTrue(array_is_list($subset));
    }

    public function testRandomSubsetPreserveKeysTrue(): void
    {
        $collection = [
            'first' => 'apple',
            'second' => 'banana',
            'third' => 'cherry',
            'fourth' => 'date',
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
                return 'item-1';
            }
        };

        $stringable2 = new class implements Stringable {
            public function __toString(): string
            {
                return 'item-2';
            }
        };

        $collection = [$stringable1, $stringable2];
        $element = $this->rendezvous->randomElement($collection);
        $this->assertContains($element, $collection);

        $subset = $this->rendezvous->randomSubset(2, $collection);
        $this->assertCount(2, $subset);
    }
}

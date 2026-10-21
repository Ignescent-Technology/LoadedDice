<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests;

use Ignescent\LoadedDice\Hashing\Hash;
use Ignescent\LoadedDice\Hashing\Hmac;
use Ignescent\LoadedDice\RandomOracle;
use PHPUnit\Framework\TestCase;
use Stringable;

class RandomOracleTest extends TestCase
{
    private Hash $hash;
    private RandomOracle $oracle;

    protected function setUp(): void
    {
        $this->hash = new Hmac('test-secret-key', 'sha256');
        $this->oracle = new RandomOracle($this->hash);
    }

    public function testFloatReturnsDeterministicValueInRange(): void
    {
        $val1 = $this->oracle->float('user-123');
        $val2 = $this->oracle->float('user-123');

        $this->assertSame($val1, $val2);
        $this->assertGreaterThanOrEqual(0.0, $val1);
        $this->assertLessThan(1.0, $val1);
    }

    public function testFloatReturnsDifferentValuesForDifferentInputs(): void
    {
        $val1 = $this->oracle->float('item-a');
        $val2 = $this->oracle->float('item-b');

        $this->assertNotSame($val1, $val2);
    }

    public function testFloatCalculatesExpectedValueFromHash(): void
    {
        $stubHash = $this->createStub(Hash::class);
        $stubHash->method('hash')->willReturn('0000000000000000');

        $oracle = new RandomOracle($stubHash);
        $this->assertSame(0.0, $oracle->float('test'));
    }

    public function testBooleanReturnsDeterministicValue(): void
    {
        $bool1 = $this->oracle->boolean('user-123');
        $bool2 = $this->oracle->boolean('user-123');

        $this->assertSame($bool1, $bool2);
        $this->assertIsBool($bool1);
    }

    public function testBooleanWithZeroProbabilityAlwaysReturnsFalse(): void
    {
        $result = $this->oracle->boolean('test-value', 0.0);
        $this->assertFalse($result);
    }

    public function testBooleanWithFullProbabilityAlwaysReturnsTrue(): void
    {
        $result = $this->oracle->boolean('test-value', 1.0);
        $this->assertTrue($result);
    }

    public function testBooleanThresholdLogic(): void
    {
        $stubHash = $this->createStub(Hash::class);
        $oracle = new RandomOracle($stubHash);

        $floatValue = $oracle->float('test');

        $this->assertTrue($oracle->boolean('test', $floatValue + 0.1));
        $this->assertFalse($oracle->boolean('test', $floatValue - 0.0001));
    }

    public function testStringableObjectsAreSupported(): void
    {
        $stringable = new class implements Stringable {
            public function __toString(): string
            {
                return 'stringable-value';
            }
        };

        $floatFromStringable = $this->oracle->float($stringable);
        $floatFromString = $this->oracle->float('stringable-value');
        $this->assertSame($floatFromString, $floatFromStringable);

        $boolFromStringable = $this->oracle->boolean($stringable);
        $boolFromString = $this->oracle->boolean('stringable-value');
        $this->assertSame($boolFromString, $boolFromStringable);
    }
}

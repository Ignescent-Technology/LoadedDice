<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests\Bandits;

use Ignescent\LoadedDice\Bandits\BanditStrategy;
use Ignescent\LoadedDice\Bandits\EpsilonFallthrough;
use Ignescent\LoadedDice\Bandits\ExperimentalData;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

class EpsilonFallthroughTest extends TestCase
{
    public function testImplementsBanditStrategy(): void
    {
        $strategy = new EpsilonFallthrough(
            new Randomizer(),
            new class implements BanditStrategy {
                public function pull(ExperimentalData $experimentalData): string
                {
                    return 'arm';
                }
            }
        );

        $this->assertInstanceOf(BanditStrategy::class, $strategy);
    }

    public function testPureExploitationWhenEpsilonZero(): void
    {
        $mock = new class implements BanditStrategy {
            public int $calls = 0;

            public function pull(ExperimentalData $experimentalData): string
            {
                $this->calls++;
                return 'exploited_arm';
            }
        };

        $strategy = new EpsilonFallthrough(new Randomizer(), $mock, epsilon: 0.0);
        $data = new ExperimentalData();
        $data->initializeArms(['arm_1', 'arm_2', 'arm_3']);

        for ($i = 0; $i < 50; $i++) {
            $this->assertSame('exploited_arm', $strategy->pull($data));
        }

        $this->assertSame(50, $mock->calls);
    }

    public function testPureExplorationWhenEpsilonOne(): void
    {
        $mock = new class implements BanditStrategy {
            public int $calls = 0;

            public function pull(ExperimentalData $experimentalData): string
            {
                $this->calls++;
                return 'never_called';
            }
        };

        $strategy = new EpsilonFallthrough(new Randomizer(new Mt19937(42)), $mock, epsilon: 1.0);
        $data = new ExperimentalData();
        $data->initializeArms(['arm_a', 'arm_b']);

        $counts = ['arm_a' => 0, 'arm_b' => 0];

        for ($i = 0; $i < 1000; $i++) {
            $chosen = $strategy->pull($data);
            $counts[$chosen]++;
        }

        $this->assertSame(0, $mock->calls);
        $this->assertEqualsWithDelta(500, $counts['arm_a'], 75);
        $this->assertEqualsWithDelta(500, $counts['arm_b'], 75);
    }

    public function testExplorationVsExploitationRate(): void
    {
        $mock = new class implements BanditStrategy {
            public int $calls = 0;

            public function pull(ExperimentalData $experimentalData): string
            {
                $this->calls++;
                return 'greedy_arm';
            }
        };

        $strategy = new EpsilonFallthrough(new Randomizer(new Mt19937(123)), $mock, epsilon: 0.2);
        $data = new ExperimentalData();
        $data->initializeArms(['greedy_arm', 'other_arm']);

        $trials = 2000;
        for ($i = 0; $i < $trials; $i++) {
            $strategy->pull($data);
        }

        // Decorated strategy should be called approximately (1 - 0.2) * 2000 = 1600 times
        $this->assertEqualsWithDelta(1600, $mock->calls, 100);
    }
}

<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests\Bandits;

use Ignescent\LoadedDice\Bandits\BanditStrategy;
use Ignescent\LoadedDice\Bandits\ExperimentalData;
use Ignescent\LoadedDice\Bandits\Greedy;
use PHPUnit\Framework\TestCase;

class GreedyTest extends TestCase
{
    public function testImplementsBanditStrategy(): void
    {
        $greedy = new Greedy();

        $this->assertInstanceOf(BanditStrategy::class, $greedy);
    }

    public function testPullsArmWithHighestAverageReward(): void
    {
        $greedy = new Greedy();
        $data = new ExperimentalData();

        $data->update('arm_a', 0.2);
        $data->update('arm_a', 0.4); // avg = 0.3
        $data->update('arm_b', 0.8);
        $data->update('arm_b', 0.9); // avg = 0.85
        $data->update('arm_c', 0.6); // avg = 0.6

        $this->assertSame('arm_b', $greedy->pull($data));
    }

    public function testSingleArm(): void
    {
        $greedy = new Greedy();
        $data = new ExperimentalData();

        $data->update('only_arm', 0.5);

        $this->assertSame('only_arm', $greedy->pull($data));
    }
}

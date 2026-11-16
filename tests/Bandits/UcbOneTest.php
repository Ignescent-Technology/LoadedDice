<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests\Bandits;

use Ignescent\LoadedDice\Bandits\Bandit;
use Ignescent\LoadedDice\Bandits\BanditStrategy;
use Ignescent\LoadedDice\Bandits\ExperimentalData;
use Ignescent\LoadedDice\Bandits\UcbOne;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

class UcbOneTest extends TestCase
{
    public function testImplementsBanditStrategy(): void
    {
        $ucbOne = new UcbOne();

        $this->assertInstanceOf(BanditStrategy::class, $ucbOne);
    }

    public function testPullsHighestUcbScore(): void
    {
        $ucbOne = new UcbOne();
        $data = new ExperimentalData();

        // Both arms pulled equal number of times (10 each)
        for ($i = 0; $i < 10; $i++) {
            $data->update('arm_a', 0.2);
            $data->update('arm_b', 0.8);
        }

        // arm_b has higher average, equal variance bonus -> higher UCB score
        $this->assertSame('arm_b', $ucbOne->pull($data));
    }

    public function testExploresUnderSampledArmDueToUncertaintyBonus(): void
    {
        $ucbOne = new UcbOne();
        $data = new ExperimentalData();

        // arm_exploited pulled 100 times with high average 0.8
        for ($i = 0; $i < 100; $i++) {
            $data->update('arm_exploited', 0.8);
        }

        // arm_uncertain pulled only 1 time with slightly lower reward 0.6
        $data->update('arm_uncertain', 0.6);

        // UCB bonus for arm_uncertain is sqrt(2 * ln(101) / 1) ≈ 3.038
        // Score for arm_uncertain ≈ 3.638 > Score for arm_exploited (0.8 + 0.304 ≈ 1.104)
        $this->assertSame('arm_uncertain', $ucbOne->pull($data));
    }

    public function testBanditSimulationConvergence(): void
    {
        $randomizer = new Randomizer(new Mt19937(42));
        $strategy = new UcbOne();
        $bandit = new Bandit($strategy);

        $trueProbabilities = [
            'arm_1' => 0.2,
            'arm_2' => 0.8,
            'arm_3' => 0.4,
        ];

        // Initialize each arm with 1 initial pull
        foreach ($trueProbabilities as $arm => $prob) {
            $reward = $randomizer->nextFloat() < $prob ? 1.0 : 0.0;
            $bandit->update($arm, $reward);
        }

        $pullCounts = ['arm_1' => 1, 'arm_2' => 1, 'arm_3' => 1];

        for ($step = 0; $step < 500; $step++) {
            $arm = $bandit->pull();
            $pullCounts[$arm]++;

            $reward = $randomizer->nextFloat() < $trueProbabilities[$arm] ? 1.0 : 0.0;
            $bandit->update($arm, $reward);
        }

        // arm_2 has the highest reward probability (0.8), so it should be pulled the most
        $this->assertGreaterThan($pullCounts['arm_1'], $pullCounts['arm_2']);
        $this->assertGreaterThan($pullCounts['arm_3'], $pullCounts['arm_2']);
        $this->assertGreaterThan(350, $pullCounts['arm_2']);
    }
}

<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests\Bandits;

use Ignescent\LoadedDice\Bandits\Bandit;
use Ignescent\LoadedDice\Bandits\BanditStrategy;
use Ignescent\LoadedDice\Bandits\EpsilonGreedy;
use Ignescent\LoadedDice\Bandits\ExperimentalData;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

class EpsilonGreedyTest extends TestCase
{
    public function testImplementsBanditStrategy(): void
    {
        $epsilonGreedy = new EpsilonGreedy(new Randomizer());

        $this->assertInstanceOf(BanditStrategy::class, $epsilonGreedy);
        $this->assertSame(0.1, $epsilonGreedy->epsilon);
    }

    public function testFavorsBestArm(): void
    {
        // With epsilon = 0.0, it behaves purely greedily
        $epsilonGreedy = new EpsilonGreedy(new Randomizer(), epsilon: 0.0);
        $data = new ExperimentalData();

        $data->update('poor_arm', 0.2);
        $data->update('best_arm', 0.9);

        for ($i = 0; $i < 20; $i++) {
            $this->assertSame('best_arm', $epsilonGreedy->pull($data));
        }
    }

    public function testPureExplorationWithEpsilonOne(): void
    {
        $epsilonGreedy = new EpsilonGreedy(new Randomizer(new Mt19937(42)), epsilon: 1.0);
        $data = new ExperimentalData();
        $data->initializeArms(['arm_1', 'arm_2']);

        $data->update('arm_1', 1.0);
        $data->update('arm_2', 0.0);

        $counts = ['arm_1' => 0, 'arm_2' => 0];
        $trials = 1000;

        for ($i = 0; $i < $trials; $i++) {
            $chosen = $epsilonGreedy->pull($data);
            $counts[$chosen]++;
        }

        $this->assertEqualsWithDelta(500, $counts['arm_1'], 75);
        $this->assertEqualsWithDelta(500, $counts['arm_2'], 75);
    }

    public function testBanditSimulationConvergence(): void
    {
        $randomizer = new Randomizer(new Mt19937(42));
        $strategy = new EpsilonGreedy($randomizer, epsilon: 0.1);
        $bandit = new Bandit($strategy);

        $trueProbabilities = [
            'arm_1' => 0.1,
            'arm_2' => 0.9,
            'arm_3' => 0.3,
        ];

        // Seed initial arm knowledge
        $bandit->bulkUpdate([
            'arm_1' => 0.1,
            'arm_2' => 0.9,
            'arm_3' => 0.3,
        ]);

        $pullCounts = ['arm_1' => 0, 'arm_2' => 0, 'arm_3' => 0];

        for ($step = 0; $step < 500; $step++) {
            $arm = $bandit->pull();
            $pullCounts[$arm]++;

            $reward = $randomizer->nextFloat() < $trueProbabilities[$arm] ? 1.0 : 0.0;
            $bandit->update($arm, $reward);
        }

        // arm_2 should be pulled the vast majority of the time (around 90% of the time)
        $this->assertGreaterThan($pullCounts['arm_1'], $pullCounts['arm_2']);
        $this->assertGreaterThan($pullCounts['arm_3'], $pullCounts['arm_2']);
        $this->assertGreaterThan(400, $pullCounts['arm_2']);
    }
}

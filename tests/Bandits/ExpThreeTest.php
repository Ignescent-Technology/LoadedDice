<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests\Bandits;

use Ignescent\LoadedDice\Bandits\Bandit;
use Ignescent\LoadedDice\Bandits\BanditStrategy;
use Ignescent\LoadedDice\Bandits\ExperimentalData;
use Ignescent\LoadedDice\Bandits\ExpThree;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

class ExpThreeTest extends TestCase
{
    public function testImplementsBanditStrategy(): void
    {
        $expThree = new ExpThree(new Randomizer());

        $this->assertInstanceOf(BanditStrategy::class, $expThree);
    }

    public function testSingleArmReturnsArm(): void
    {
        $expThree = new ExpThree(new Randomizer());
        $data = new ExperimentalData();
        $data->initializeArm('only_arm');

        $this->assertSame('only_arm', $expThree->pull($data));
    }

    public function testZeroPullsSamplesUniformly(): void
    {
        $expThree = new ExpThree(new Randomizer(new Mt19937(42)));
        $data = new ExperimentalData();
        $data->initializeArms(['arm_a', 'arm_b', 'arm_c']);

        $counts = ['arm_a' => 0, 'arm_b' => 0, 'arm_c' => 0];
        $iterations = 3000;

        for ($i = 0; $i < $iterations; $i++) {
            $chosen = $expThree->pull($data);
            $counts[$chosen]++;
        }

        // Each arm should be chosen roughly 1000 times (1/3 each)
        $this->assertEqualsWithDelta(1000, $counts['arm_a'], 150);
        $this->assertEqualsWithDelta(1000, $counts['arm_b'], 150);
        $this->assertEqualsWithDelta(1000, $counts['arm_c'], 150);
    }

    public function testFavorsArmWithHigherReward(): void
    {
        $expThree = new ExpThree(new Randomizer(new Mt19937(12345)));
        $data = new ExperimentalData();
        $data->initializeArms(['bad_arm', 'good_arm']);

        // Record history: good_arm has high average, bad_arm has low average
        for ($i = 0; $i < 50; $i++) {
            $data->update('good_arm', 1.0);
            $data->update('bad_arm', 0.1);
        }

        $counts = ['bad_arm' => 0, 'good_arm' => 0];
        $trials = 1000;

        for ($i = 0; $i < $trials; $i++) {
            $chosen = $expThree->pull($data);
            $counts[$chosen]++;
        }

        $this->assertGreaterThan($counts['bad_arm'], $counts['good_arm']);
        $this->assertGreaterThan(700, $counts['good_arm']);
    }

    public function testCustomGammaOverride(): void
    {
        // When gamma = 1.0, exploration is pure uniform random despite reward history
        $expThree = new ExpThree(new Randomizer(new Mt19937(99)), gamma: 1.0);
        $data = new ExperimentalData();
        $data->initializeArms(['arm_1', 'arm_2']);

        for ($i = 0; $i < 100; $i++) {
            $data->update('arm_1', 1.0);
            $data->update('arm_2', 0.0);
        }

        $counts = ['arm_1' => 0, 'arm_2' => 0];
        $trials = 2000;

        for ($i = 0; $i < $trials; $i++) {
            $chosen = $expThree->pull($data);
            $counts[$chosen]++;
        }

        $this->assertEqualsWithDelta(1000, $counts['arm_1'], 150);
        $this->assertEqualsWithDelta(1000, $counts['arm_2'], 150);
    }

    public function testBanditSimulationConvergence(): void
    {
        $randomizer = new Randomizer(new Mt19937(42));
        $strategy = new ExpThree($randomizer);
        $bandit = new Bandit($strategy);

        $trueProbabilities = [
            'arm_1' => 0.2,
            'arm_2' => 0.8,
            'arm_3' => 0.4,
        ];

        $bandit->bulkUpdate([
            'arm_1' => 0.0,
            'arm_2' => 0.0,
            'arm_3' => 0.0,
        ]);

        $pullCounts = ['arm_1' => 0, 'arm_2' => 0, 'arm_3' => 0];

        for ($step = 0; $step < 500; $step++) {
            $arm = $bandit->pull();
            $pullCounts[$arm]++;

            $reward = $randomizer->nextFloat() < $trueProbabilities[$arm] ? 1.0 : 0.0;
            $bandit->update($arm, $reward);
        }

        // arm_2 has the highest reward probability (0.8), so it should be pulled most often
        $this->assertGreaterThan($pullCounts['arm_1'], $pullCounts['arm_2']);
        $this->assertGreaterThan($pullCounts['arm_3'], $pullCounts['arm_2']);
    }
}

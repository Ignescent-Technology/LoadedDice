<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests\Bandits;

use Ignescent\LoadedDice\Bandits\Bandit;
use Ignescent\LoadedDice\Bandits\BanditStrategy;
use Ignescent\LoadedDice\Bandits\ExperimentalData;
use Ignescent\LoadedDice\Bandits\ThompsonSamplingBeta;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

class ThompsonSamplingBetaTest extends TestCase
{
    public function testImplementsBanditStrategy(): void
    {
        $strategy = new ThompsonSamplingBeta(new Randomizer());

        $this->assertInstanceOf(BanditStrategy::class, $strategy);
    }

    public function testSingleArmReturnsArm(): void
    {
        $strategy = new ThompsonSamplingBeta(new Randomizer());
        $data = new ExperimentalData();
        $data->initializeArm('only_arm');

        $this->assertSame('only_arm', $strategy->pull($data));
    }

    public function testZeroPullsSamplesUniformly(): void
    {
        $strategy = new ThompsonSamplingBeta(new Randomizer(new Mt19937(42)));
        $data = new ExperimentalData();
        $data->initializeArms(['arm_a', 'arm_b', 'arm_c']);

        $counts = ['arm_a' => 0, 'arm_b' => 0, 'arm_c' => 0];
        $iterations = 3000;

        for ($i = 0; $i < $iterations; $i++) {
            $chosen = $strategy->pull($data);
            $counts[$chosen]++;
        }

        // Under uniform prior Beta(1, 1), samples are i.i.d. Uniform(0,1) -> each arm equally likely
        $this->assertEqualsWithDelta(1000, $counts['arm_a'], 150);
        $this->assertEqualsWithDelta(1000, $counts['arm_b'], 150);
        $this->assertEqualsWithDelta(1000, $counts['arm_c'], 150);
    }

    public function testFavorsArmWithHigherSuccesses(): void
    {
        $strategy = new ThompsonSamplingBeta(new Randomizer(new Mt19937(12345)));
        $data = new ExperimentalData();
        $data->initializeArms(['bad_arm', 'good_arm']);

        // Record history: good_arm has 90 successes / 10 failures, bad_arm has 10 successes / 90 failures
        for ($i = 0; $i < 90; $i++) {
            $data->update('good_arm', 1.0);
            $data->update('bad_arm', 0.0);
        }
        for ($i = 0; $i < 10; $i++) {
            $data->update('good_arm', 0.0);
            $data->update('bad_arm', 1.0);
        }

        $counts = ['bad_arm' => 0, 'good_arm' => 0];
        $trials = 1000;

        for ($i = 0; $i < $trials; $i++) {
            $chosen = $strategy->pull($data);
            $counts[$chosen]++;
        }

        $this->assertGreaterThan($counts['bad_arm'], $counts['good_arm']);
        $this->assertGreaterThan(950, $counts['good_arm']);
    }

    public function testHandlesFractionalRewards(): void
    {
        $strategy = new ThompsonSamplingBeta(new Randomizer(new Mt19937(777)));
        $data = new ExperimentalData();

        for ($i = 0; $i < 50; $i++) {
            $data->update('fractional_high', 0.8);
            $data->update('fractional_low', 0.2);
        }

        $counts = ['fractional_high' => 0, 'fractional_low' => 0];
        $trials = 1000;

        for ($i = 0; $i < $trials; $i++) {
            $chosen = $strategy->pull($data);
            $counts[$chosen]++;
        }

        $this->assertGreaterThan($counts['fractional_low'], $counts['fractional_high']);
        $this->assertGreaterThan(900, $counts['fractional_high']);
    }

    public function testBanditSimulationConvergence(): void
    {
        $randomizer = new Randomizer(new Mt19937(42));
        $strategy = new ThompsonSamplingBeta($randomizer);
        $bandit = new Bandit($strategy);

        $trueProbabilities = [
            'arm_1' => 0.1,
            'arm_2' => 0.85,
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

        // arm_2 has the highest true expected payoff (0.85) and should dominate decisions
        $this->assertGreaterThan($pullCounts['arm_1'], $pullCounts['arm_2']);
        $this->assertGreaterThan($pullCounts['arm_3'], $pullCounts['arm_2']);
        $this->assertGreaterThan(400, $pullCounts['arm_2']);
    }
}

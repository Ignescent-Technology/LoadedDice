<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests\Bandits;

use Ignescent\LoadedDice\Bandits\ActionElimination;
use Ignescent\LoadedDice\Bandits\Bandit;
use Ignescent\LoadedDice\Bandits\BanditStrategy;
use Ignescent\LoadedDice\Bandits\ExperimentalData;
use Ignescent\LoadedDice\Bandits\Greedy;
use Ignescent\LoadedDice\Bandits\UcbOne;
use PHPUnit\Framework\TestCase;

class ActionEliminationTest extends TestCase
{
    public function testImplementsBanditStrategy(): void
    {
        $mockStrategy = new class implements BanditStrategy {
            public function pull(ExperimentalData $experimentalData): string
            {
                return 'arm';
            }
        };

        $decorator = new ActionElimination($mockStrategy);

        $this->assertInstanceOf(BanditStrategy::class, $decorator);
    }

    public function testSingleArmDelegatesDirectly(): void
    {
        $mockStrategy = new class implements BanditStrategy {
            public ?ExperimentalData $receivedData = null;

            public function pull(ExperimentalData $experimentalData): string
            {
                $this->receivedData = $experimentalData;
                return 'single_arm';
            }
        };

        $decorator = new ActionElimination($mockStrategy);
        $data = new ExperimentalData(['single_arm' => [0.5]]);

        $result = $decorator->pull($data);

        $this->assertSame('single_arm', $result);
        $this->assertSame(['single_arm'], $mockStrategy->receivedData->getArms());
    }

    public function testZeroTotalPullsDelegatesWithAllArms(): void
    {
        $mockStrategy = new class implements BanditStrategy {
            public ?ExperimentalData $receivedData = null;

            public function pull(ExperimentalData $experimentalData): string
            {
                $this->receivedData = $experimentalData;
                return 'arm_1';
            }
        };

        $decorator = new ActionElimination($mockStrategy);
        $data = new ExperimentalData();
        $data->initializeArms(['arm_1', 'arm_2', 'arm_3']);

        $result = $decorator->pull($data);

        $this->assertSame('arm_1', $result);
        $this->assertSame(['arm_1', 'arm_2', 'arm_3'], $mockStrategy->receivedData->getArms());
    }

    public function testUnpulledArmsAreNotEliminated(): void
    {
        $mockStrategy = new class implements BanditStrategy {
            public ?ExperimentalData $receivedData = null;

            public function pull(ExperimentalData $experimentalData): string
            {
                $this->receivedData = $experimentalData;
                return 'arm_unpulled';
            }
        };

        $decorator = new ActionElimination($mockStrategy);
        $data = new ExperimentalData();
        $data->initializeArms(['arm_pulled', 'arm_unpulled']);

        for ($i = 0; $i < 50; $i++) {
            $data->update('arm_pulled', 0.9);
        }

        $decorator->pull($data);

        $this->assertContains('arm_unpulled', $mockStrategy->receivedData->getArms());
        $this->assertContains('arm_pulled', $mockStrategy->receivedData->getArms());
    }

    public function testEliminatesArmWhoseUcbIsLowerThanBestLcb(): void
    {
        $mockStrategy = new class implements BanditStrategy {
            public ?ExperimentalData $receivedData = null;

            public function pull(ExperimentalData $experimentalData): string
            {
                $this->receivedData = $experimentalData;
                return $experimentalData->getArms()[0];
            }
        };

        $decorator = new ActionElimination($mockStrategy);
        $data = new ExperimentalData();

        // 100 pulls on arm_good with reward 1.0 (mean = 1.0)
        // 100 pulls on arm_bad with reward 0.0 (mean = 0.0)
        // Total pulls T = 200, ln(200) ≈ 5.2983
        // Radius for both = sqrt(2 * 5.2983 / 100) ≈ 0.3255
        // arm_good: UCB ≈ 1.3255, LCB ≈ 0.6745
        // arm_bad:  UCB ≈ 0.3255, LCB ≈ -0.3255
        // arm_bad UCB (0.3255) < arm_good LCB (0.6745) -> arm_bad eliminated!
        for ($i = 0; $i < 100; $i++) {
            $data->update('arm_good', 1.0);
            $data->update('arm_bad', 0.0);
        }

        $decorator->pull($data);

        $this->assertNotNull($mockStrategy->receivedData);
        $this->assertSame(['arm_good'], $mockStrategy->receivedData->getArms());
        $this->assertNotContains('arm_bad', $mockStrategy->receivedData->getArms());
    }

    public function testRetainsOverlappingArms(): void
    {
        $mockStrategy = new class implements BanditStrategy {
            public ?ExperimentalData $receivedData = null;

            public function pull(ExperimentalData $experimentalData): string
            {
                $this->receivedData = $experimentalData;
                return 'arm_a';
            }
        };

        $decorator = new ActionElimination($mockStrategy);
        $data = new ExperimentalData();

        // 10 pulls on arm_a with reward 0.6
        // 10 pulls on arm_b with reward 0.5
        // Total pulls T = 20, ln(20) ≈ 2.9957
        // Radius = sqrt(2 * 2.9957 / 10) ≈ 0.774
        // arm_a: UCB ≈ 1.374, LCB ≈ -0.174
        // arm_b: UCB ≈ 1.274, LCB ≈ -0.274
        // arm_b UCB (1.274) > arm_a LCB (-0.174) -> both arms retained!
        for ($i = 0; $i < 10; $i++) {
            $data->update('arm_a', 0.6);
            $data->update('arm_b', 0.5);
        }

        $decorator->pull($data);

        $this->assertNotNull($mockStrategy->receivedData);
        $this->assertEqualsCanonicalizing(['arm_a', 'arm_b'], $mockStrategy->receivedData->getArms());
    }

    public function testMultiArmElimination(): void
    {
        $mockStrategy = new class implements BanditStrategy {
            public ?ExperimentalData $receivedData = null;

            public function pull(ExperimentalData $experimentalData): string
            {
                $this->receivedData = $experimentalData;
                return 'arm_best';
            }
        };

        $decorator = new ActionElimination($mockStrategy);
        $data = new ExperimentalData();

        // T = 300, 100 pulls each
        // ln(300) ≈ 5.7038, radius = sqrt(2 * 5.7038 / 100) ≈ 0.33775
        // arm_best: avg 0.95 -> LCB = 0.61225, UCB = 1.28775
        // arm_mid:  avg 0.70 -> LCB = 0.36225, UCB = 1.03775 (UCB > 0.61225 -> survives)
        // arm_poor: avg 0.10 -> LCB = -0.23775, UCB = 0.43775 (UCB < 0.61225 -> eliminated)
        for ($i = 0; $i < 100; $i++) {
            $data->update('arm_best', 0.95);
            $data->update('arm_mid', 0.70);
            $data->update('arm_poor', 0.10);
        }

        $decorator->pull($data);

        $this->assertNotNull($mockStrategy->receivedData);
        $this->assertEqualsCanonicalizing(['arm_best', 'arm_mid'], $mockStrategy->receivedData->getArms());
        $this->assertNotContains('arm_poor', $mockStrategy->receivedData->getArms());
    }

    public function testIntegrationWithGreedyStrategy(): void
    {
        $greedyWithElimination = new ActionElimination(new Greedy());
        $bandit = new Bandit($greedyWithElimination);

        for ($i = 0; $i < 100; $i++) {
            $bandit->update('optimal', 0.9);
            $bandit->update('suboptimal', 0.1);
        }

        $this->assertSame('optimal', $bandit->pull());
    }

    public function testIntegrationWithUcbOneStrategy(): void
    {
        $ucbWithElimination = new ActionElimination(new UcbOne());
        $bandit = new Bandit($ucbWithElimination);

        for ($i = 0; $i < 100; $i++) {
            $bandit->update('arm_1', 0.85);
            $bandit->update('arm_2', 0.15);
        }

        $this->assertSame('arm_1', $bandit->pull());
    }
}

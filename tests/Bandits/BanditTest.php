<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests\Bandits;

use Ignescent\LoadedDice\Bandits\Bandit;
use Ignescent\LoadedDice\Bandits\BanditStrategy;
use Ignescent\LoadedDice\Bandits\ExperimentalData;
use PHPUnit\Framework\TestCase;

class BanditTest extends TestCase
{
    public function testDelegatesPullToStrategy(): void
    {
        $strategy = new class implements BanditStrategy {
            public int $pullCount = 0;
            public ?ExperimentalData $passedData = null;

            public function pull(ExperimentalData $experimentalData): string
            {
                $this->pullCount++;
                $this->passedData = $experimentalData;
                return 'mock_arm';
            }
        };

        $bandit = new Bandit($strategy);
        $result = $bandit->pull();

        $this->assertSame('mock_arm', $result);
        $this->assertSame(1, $strategy->pullCount);
        $this->assertInstanceOf(ExperimentalData::class, $strategy->passedData);
    }

    public function testUsesInjectedExperimentalData(): void
    {
        $customData = new ExperimentalData();
        $customData->initializeArm('arm_1');

        $strategy = new class implements BanditStrategy {
            public ?ExperimentalData $passedData = null;

            public function pull(ExperimentalData $experimentalData): string
            {
                $this->passedData = $experimentalData;
                return 'arm_1';
            }
        };

        $bandit = new Bandit($strategy, $customData);
        $bandit->pull();

        $this->assertSame($customData, $strategy->passedData);
        $this->assertSame(['arm_1'], $strategy->passedData->getArms());
    }

    public function testUpdateAndBulkUpdateModifyExperimentalData(): void
    {
        $customData = new ExperimentalData();

        $strategy = new class implements BanditStrategy {
            public function pull(ExperimentalData $experimentalData): string
            {
                return $experimentalData->getArms()[0];
            }
        };

        $bandit = new Bandit($strategy, $customData);

        $bandit->update('arm_a', 0.5);
        $this->assertSame(1, $customData->getTotalPullCount());
        $this->assertSame(0.5, $customData->getArmAverage('arm_a'));

        $bandit->bulkUpdate([
            'arm_a' => 0.7,
            'arm_b' => 0.9,
        ]);

        $this->assertSame(3, $customData->getTotalPullCount());
        $this->assertSame(0.6, $customData->getArmAverage('arm_a'));
        $this->assertSame(0.9, $customData->getArmAverage('arm_b'));
    }
}

<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Tests\Bandits;

use Ignescent\LoadedDice\Bandits\ExperimentalData;
use PHPUnit\Framework\TestCase;

class ExperimentalDataTest extends TestCase
{
    public function testInitializeArmAndGetArms(): void
    {
        $data = new ExperimentalData();

        $data->initializeArm('arm_1');
        $this->assertSame(['arm_1'], $data->getArms());
        $this->assertSame([], $data->getArmHistory('arm_1'));
        $this->assertSame(0, $data->getArmPullCount('arm_1'));
        $this->assertSame(0, $data->getTotalPullCount());

        // Initializing again should not overwrite existing history
        $data->update('arm_1', 1.0);
        $data->initializeArm('arm_1');
        $this->assertSame([1.0], $data->getArmHistory('arm_1'));
    }

    public function testInitializeArms(): void
    {
        $data = new ExperimentalData();
        $data->initializeArms(['arm_a', 'arm_b', 'arm_c']);

        $this->assertSame(['arm_a', 'arm_b', 'arm_c'], $data->getArms());
    }

    public function testUpdateAndAverages(): void
    {
        $data = new ExperimentalData();

        $data->update('arm_1', 0.2);
        $data->update('arm_1', 0.8);
        $data->update('arm_2', 0.5);

        $this->assertSame(3, $data->getTotalPullCount());
        $this->assertSame(2, $data->getArmPullCount('arm_1'));
        $this->assertSame(1, $data->getArmPullCount('arm_2'));
        $this->assertSame([0.2, 0.8], $data->getArmHistory('arm_1'));
        $this->assertSame([0.5], $data->getArmHistory('arm_2'));
        $this->assertEqualsWithDelta(0.5, $data->getArmAverage('arm_1'), 1e-9);
        $this->assertEqualsWithDelta(0.5, $data->getArmAverage('arm_2'), 1e-9);
    }

    public function testBulkUpdate(): void
    {
        $data = new ExperimentalData();
        $data->bulkUpdate([
            'arm_x' => 1.0,
            'arm_y' => 0.0,
        ]);

        $this->assertSame(2, $data->getTotalPullCount());
        $this->assertSame(1.0, $data->getArmAverage('arm_x'));
        $this->assertSame(0.0, $data->getArmAverage('arm_y'));
    }

    public function testBestArm(): void
    {
        $scores = [
            'arm_low' => 0.1,
            'arm_high' => 0.9,
            'arm_mid' => 0.5,
        ];

        $this->assertSame('arm_high', ExperimentalData::bestArm($scores));
    }

    public function testAssertHasArms(): void
    {
        $data = new ExperimentalData();
        $data->initializeArm('arm_1');

        $data->assertHasArms();
        $this->assertTrue(true);
    }
}

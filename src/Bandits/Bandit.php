<?php

namespace Ignescent\LoadedDice\Bandits;

/**
 * Multi-armed bandit orchestrator that executes a selection strategy
 * and tracks arm reward histories in experimental data.
 */
final class Bandit
{
    private ExperimentalData $experimentalData;

    public function __construct(
        private readonly BanditStrategy $strategy,
        ?ExperimentalData $experimentalData = null,
    )
    {
        $this->experimentalData = $experimentalData ?? new ExperimentalData();
    }

    public function pull(): string
    {
        return $this->strategy->pull($this->experimentalData);
    }

    public function bulkUpdate(array $rewardsByArm): void
    {
        $this->experimentalData->bulkUpdate($rewardsByArm);
    }

    public function update(string $arm, float $reward): void
    {
        $this->experimentalData->update($arm, $reward);
    }

    public static function bestArm(array $scoresByArm): string
    {
        $bestArm = null;

        foreach ($scoresByArm as $arm => $score) {
            if ($bestArm === null || $score > $scoresByArm[$bestArm]) {
                $bestArm = $arm;
            }
        }

        assert($bestArm !== null);

        return $bestArm;
    }
}

<?php

namespace Ignescent\LoadedDice\Bandits;

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
}

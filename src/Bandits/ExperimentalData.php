<?php

namespace Ignescent\LoadedDice\Bandits;

final class ExperimentalData
{
    private array $arms;

    private int $totalPullCount;

    /**
     * @param array<string, array<float>> $rewardsByArm
     */
    public function __construct(array $rewardsByArm = [])
    {
        $this->arms = $rewardsByArm;
        $this->totalPullCount = array_sum(array_map(fn ($rewards) => count($rewards), $rewardsByArm));
    }

    public function initializeArms(array $arms): void
    {
        foreach ($arms as $arm) {
            $this->initializeArm($arm);
        }
    }

    public function initializeArm(string $arm): void
    {
        if (!isset($this->arms[$arm])) $this->arms[$arm] = [];
    }

    public function bulkUpdate(array $rewardsByArm): void
    {
        foreach ($rewardsByArm as $arm => $reward) {
            $this->update($arm, $reward);
        }
    }

    public function update(string $arm, float $reward): void
    {
        $this->initializeArm($arm);

        $this->arms[$arm][] = $reward;
        $this->totalPullCount++;
    }

    public function assertHasArms(): void
    {
        assert(!empty($this->arms));
    }

    public function getArms(): array
    {
        return array_keys($this->arms);
    }

    public function getArmAverage(string $arm): float
    {
        return array_sum($this->getArmHistory($arm)) / $this->getArmPullCount($arm);
    }

    public function getArmHistory(string $arm): array
    {
        return $this->arms[$arm];
    }

    public function getArmPullCount(string $arm): int
    {
        return count($this->getArmHistory($arm));
    }

    public function getTotalPullCount(): int
    {
        return $this->totalPullCount;
    }
}

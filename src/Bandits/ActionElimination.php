<?php

namespace Ignescent\LoadedDice\Bandits;

/**
 * Multi-armed bandit strategy decorator that prunes arms whose UCB1 upper confidence bound
 * falls below the highest lower confidence bound across all arms.
 */
final class ActionElimination implements BanditStrategy
{
    public function __construct(
        protected BanditStrategy $decorated,
    )
    {
    }

    public function pull(ExperimentalData $experimentalData): string
    {
        $experimentalData->assertHasArms();

        $arms = $experimentalData->getArms();

        if (count($arms) <= 1 || $experimentalData->getTotalPullCount() === 0) {
            return $this->decorated->pull($experimentalData);
        }

        $survivingArms = $this->calculateSurvivingArms($experimentalData, $arms);

        if (count($survivingArms) === count($arms)) {
            return $this->decorated->pull($experimentalData);
        }

        $survivingData = $this->filterExperimentalData($experimentalData, $survivingArms);

        return $this->decorated->pull($survivingData);
    }

    /**
     * @param list<string> $arms
     * @return list<string>
     */
    private function calculateSurvivingArms(ExperimentalData $experimentalData, array $arms): array
    {
        [$upperScores, $lowerScores] = $this->calculateConfidenceBounds($experimentalData, $arms);
        $maxLowerScore = max($lowerScores);

        return array_values(array_filter($arms, function (string $arm) use ($upperScores, $maxLowerScore): bool {
            return $upperScores[$arm] >= $maxLowerScore;
        }));
    }

    /**
     * @param list<string> $arms
     * @return array{0: array<string, float>, 1: array<string, float>}
     */
    private function calculateConfidenceBounds(ExperimentalData $experimentalData, array $arms): array
    {
        $lnTotalPulls = log($experimentalData->getTotalPullCount());
        $upperScores = [];
        $lowerScores = [];

        foreach ($arms as $arm) {
            $pullCount = $experimentalData->getArmPullCount($arm);
            if ($pullCount === 0) {
                $upperScores[$arm] = INF;
                $lowerScores[$arm] = -INF;
            } else {
                $variancePremium = sqrt(2 * $lnTotalPulls / $pullCount);
                $average = $experimentalData->getArmAverage($arm);

                $upperScores[$arm] = $average + $variancePremium;
                $lowerScores[$arm] = $average - $variancePremium;
            }
        }

        return [$upperScores, $lowerScores];
    }

    /**
     * @param list<string> $survivingArms
     */
    private function filterExperimentalData(ExperimentalData $experimentalData, array $survivingArms): ExperimentalData
    {
        $survivingRewards = [];

        foreach ($survivingArms as $arm) {
            $survivingRewards[$arm] = $experimentalData->getArmHistory($arm);
        }

        return new ExperimentalData($survivingRewards);
    }
}

<?php

namespace Ignescent\LoadedDice\Bandits;

/**
 * UCB1 (Upper Confidence Bound 1) multi-armed bandit strategy balancing empirical means
 * with an uncertainty premium for under-explored actions.
 */
final class UcbOne implements BanditStrategy
{
    public function pull(ExperimentalData $experimentalData): string
    {
        $experimentalData->assertHasArms();

        $arms = $experimentalData->getArms();
        $lnTotalPulls = log($experimentalData->getTotalPullCount());

        $scores = array_map(function ($arm) use ($lnTotalPulls, $experimentalData) {
            $variancePremium = sqrt(2 * $lnTotalPulls / $experimentalData->getArmPullCount($arm));

            return $experimentalData->getArmAverage($arm) + $variancePremium;
        }, $arms);

        return Bandit::bestArm(array_combine($arms, $scores));
    }
}

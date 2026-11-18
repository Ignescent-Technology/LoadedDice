<?php

namespace Ignescent\LoadedDice\Bandits;

/**
 * Pure exploitation bandit strategy that selects the arm with the highest observed empirical mean.
 */
final class Greedy implements BanditStrategy
{
    public function pull(ExperimentalData $experimentalData): string
    {
        $experimentalData->assertHasArms();

        $arms = $experimentalData->getArms();

        $scores = array_map(function ($arm) use ($experimentalData) {
            return $experimentalData->getArmAverage($arm);
        }, $arms);

        return Bandit::bestArm(array_combine($arms, $scores));
    }
}
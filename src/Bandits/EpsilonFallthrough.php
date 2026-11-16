<?php

namespace Ignescent\LoadedDice\Bandits;

use Random\Randomizer;

final class EpsilonFallthrough implements BanditStrategy
{
    public function __construct(
        protected Randomizer $randomizer,
        protected BanditStrategy $decorated,
        public float $epsilon = 0.1,
    )
    {
    }

    public function pull(ExperimentalData $experimentalData): string
    {
        $experimentalData->assertHasArms();

        $arms = $experimentalData->getArms();

        if ($this->randomizer->nextFloat() < $this->epsilon) {
            $randomArmIndex = $this->randomizer->pickArrayKeys($arms, 1)[0];

            return $arms[$randomArmIndex];
        }

        return $this->decorated->pull($experimentalData);
    }
}
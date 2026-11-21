<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice;

use Exception;
use Ignescent\LoadedDice\Bandits\Bandit;
use Ignescent\LoadedDice\Bandits\ExperimentalData;
use Ignescent\LoadedDice\Bandits\ThompsonSamplingBeta;
use Ignescent\LoadedDice\Hashing\Hash;
use Ignescent\LoadedDice\Hashing\Hmac;
use Ignescent\LoadedDice\Rendezvous\RendezvousContract;
use Ignescent\LoadedDice\Rendezvous\Simple;
use Ignescent\LoadedDice\Rendezvous\Weighted;
use Ignescent\LoadedDice\WeightResolving\MapResolver;
use Random\Randomizer;
use Stringable;

/**
 * Primary entry point and convenience facade for deterministic pseudo-random generators,
 * statistical distribution samplers, multi-armed bandits, and rendezvous hashing.
 *
 * Given the same constructor key and the same arguments, functions in this class will always return
 * the same results.
 */
final readonly class Facade
{
    private RandomOracle $randomOracle;

    private Simple $simpleRendezvous;

    /**
     * @param Hash $hash
     */
    public function __construct(
        private Hash $hash,
    )
    {
        $this->randomOracle = new RandomOracle($this->hash);
        $this->simpleRendezvous = new Simple($this->hash);
    }

    /**
     * @param string $objectIdentifier Identifier for the object to be mapped to sites.
     * @param array<string, float|int> $weightedSites A map of element to weight. Missing elements will default to 1.0.
     * @param int $siteCount How many sites to return for the object..
     * @return array<string>
     * @throws Exception
     * @link https://en.wikipedia.org/wiki/Rendezvous_hashing
     */
    public static function weightedRendezvous(
        string $objectIdentifier,
        array $weightedSites,
        int $siteCount = 1,
    ): array
    {
        $rendezvous = new Weighted(
            new Hmac($objectIdentifier, "sha256"),
            new MapResolver($weightedSites)
        );

        return $rendezvous->randomSubset($siteCount, array_keys($weightedSites));
    }

    public function thompson(ExperimentalData $data, string|Stringable|null $value = null): Bandit
    {
        return new Bandit(new ThompsonSamplingBeta($this->seedRandomizer($value)), $data);
    }

    /**
     * @see RandomOracle::boolean
     */
    public function boolean(string|Stringable $value): bool
    {
        return $this->randomOracle->boolean($value);
    }

    /**
     * @see RandomOracle::integer
     */
    public function integer(string|Stringable $value, int $max = PHP_INT_MAX): int
    {
        return $this->randomOracle->integer($value, $max);
    }

    /**
     * @see RandomOracle::float
     */
    public function float(string|Stringable $value): float
    {
        return $this->randomOracle->float($value);
    }

    /**
     * @see RendezvousContract::randomElement
     */
    public function randomElement(array $collection): string|Stringable|null
    {
        return $this->simpleRendezvous->randomElement($collection);
    }

    /**
     * @throws Exception
     * @see RendezvousContract::randomSubset
     */
    public function randomSubset(int $number, array $collection, $preserveKeys = false): array
    {
        return $this->simpleRendezvous->randomSubset($number, $collection, $preserveKeys);
    }

    public function seedRandomizer(string|Stringable|null $value = null): Randomizer
    {
        if ($value === null) {
            return new Randomizer();
        }

        return new Randomizer($this->hash->seedEngine((string) $value));
    }
}

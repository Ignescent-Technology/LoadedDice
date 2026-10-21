<?php

namespace Ignescent\LoadedDice\Rendezvous;

use Exception;
use Stringable;

interface RendezvousContract
{
    /**
     * Grabs a single random element from the collection.
     *
     * Use an object key instead of an instance key and this will provide
     * an implementation of consistent hashing.
     *
     * @param array<string|Stringable> $collection
     * @return string|Stringable|null
     */
    public function randomElement(array $collection): string|Stringable|null;

    /**
     * Given a collection of strings will return $n random values.
     *
     * Use an object key instead of an instance key and this provides
     * an unweighted implementation of Rendezvous Hashing, a form of
     * consistent hashing that gives $n options instead of just one.
     *
     * @param int $number
     * @param array<string|Stringable> $collection
     * @param bool $preserveKeys
     * @return array
     * @throws Exception
     * @link https://en.wikipedia.org/wiki/Rendezvous_hashing
     */
    public function randomSubset(int $number, array $collection, bool $preserveKeys = false): array;
}

<?php

namespace Ignescent\LoadedDice;

/**
 * @private Not part of the public API, liable to change without notice.
 */
final class Utils
{
    /**
     * Converts a hash output to the bounded [0, 1) range, useful for probability calculations.
     *
     * @param string $hash A hex string from a hash function.
     * @return float  [0, 1)
     */
    public static function hashToFloat(string $hash): float
    {
        $hashBytes = (int) floor(strlen($hash) / 2);
        $workingBytes = min($hashBytes, PHP_INT_SIZE);

        if (!$workingBytes) return 0.0;

        /* hexdec treats the input as unsigned, so the output would be an unsigned int,
         * but PHP ints are always signed. That means that PHP_INT_SIZE bytes can be 2 * PHP_INT_MAX.
         */
        $value = hexdec(substr($hash, 0, $workingBytes * 2));

        // And so this is actually a float twice PHP_INT_MAX for longer hash inputs.
        $maxValue = (2 ** ($workingBytes * 8)) - 1; // Unsigned at N bytes, but that can become a float.

        return $value / $maxValue;
    }
}
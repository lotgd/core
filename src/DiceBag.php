<?php

declare (strict_types = 1);

namespace LotGD\Core;

/**
 * A collection of random number generators, with various distributions.
 */
class DiceBag
{
    /**
     * Returns true $p percent of the time, where $p is between 0 and 1.
     * @param float $p
     */
    public function chance(float $p): bool
    {
        $r = $this->uniform(0., 1.);

        return $r < $p;
    }

    /**
     * Generates a uniformly randomly number between $min and $max.
     * @param float $min
     * @param float $max
     */
    public function uniform(float $min, float $max): float
    {
        return (mt_rand(0, 100) / 100.0) * ($max - $min) + $min;
    }

    /**
     * Generates a normally distributed random number between $min and $max.
     * @param float $min
     * @param float $max
     */
    public function normal(float $min, float $max): float
    {
        if ($min > $max) {
            $tmp = $max;
            $max = $min;
            $min = $tmp;
        } elseif ($min == $max) {
            return $min;
        }

        $mean = ($max - $min) / 2;
        $r = 0;
        do {
            $u1 = mt_rand() / mt_getrandmax();
            $u2 = mt_rand() / mt_getrandmax();
            $r = sqrt(-2 * log($u1)) * cos(2 * pi() * $u2) + $mean;
        } while ($r < $min || $r > $max);

        return $r;
    }

    /**
     * This function has uniform distribution except for the extreme values, which are
     * half as likely to happen.
     * The code for this function was taken from LotGD in version 0.9.7
     * @author MightyE, JT
     * @param int $min
     * @param int $max
     * @return int
     */
    public function pseudoBell(int $min = null, int $max = null): int
    {
        if (is_null($min)) {
            return mt_rand();
        }

        $min *= 1000;

        if (is_null($max)) {
            return (int)round(mt_rand($min)/1000, 0);
        }
        $max *= 1000;

        if ($min === $max) {
            return (int)round($min/1000, 0);
        }
        elseif ($min < $max) {
            return (int)round(mt_rand($min, $max)/1000, 0);
        }
        else {
            return (int)round(mt_rand($max, $min)/1000, 0);
        }
    }
}

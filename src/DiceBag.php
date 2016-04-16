<?php

declare (strict_types = 1);

namespace LotGD\Core;

class DiceBag
{
    public function chance(float $p): bool
    {
        $r = $this->uniform(0., 1.);

        return $r < $p;
    }

    public function uniform(float $min, float $max): float
    {
        return (mt_rand(0, 100) / 100.0) * ($max - $min) + $min;
    }

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
}

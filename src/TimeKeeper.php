<?php
declare(strict_types=1);

namespace LotGD\Core;

use DateTime;

class TimeKeeper
{
    private $adjustedEpoch;
    private $theBeginning;

    private $secondsPerMinute = 60;
    private $secondsPerHour = 60 * 60;
    private $secondsPerDay = 60 * 60 * 24;
    private $secondsPerGameYear;
    private $secondsPerGameDay;
    private $secondsPerGameHour;
    private $secondsPerGameMinute;
    private $secondsPerGameSecond;

    public function __construct(DateTime $gameEpoch, int $gameOffsetSeconds, int $gameDaysPerDay)
    {
        $gameEpochCopy = clone($gameEpoch);
        $this->adjustedEpoch = $gameEpochCopy->add(
            $this->interval(0, 0, 0, 0, $gameOffsetSeconds)
        );
        $this->theBeginning = new DateTime("0000-01-01 UTC");

        $this->secondsPerGameDay = (float) $this->secondsPerDay / $gameDaysPerDay;
        $this->secondsPerGameYear = $this->secondsPerGameDay * 365;
        $this->secondsPerGameHour = $this->secondsPerGameDay / 24;
        $this->secondsPerGameMinute = $this->secondsPerGameHour / 60;
        $this->secondsPerGameSecond = $this->secondsPerGameMinute / 60;
    }

    public function isNewDay(DateTime $lastInteractionTime): bool
    {
        if ($lastInteractionTime == null) {
            return true;
        }
        $t1 = $this->gameTime();
        $t2 = $this->convertToGameTime($lastInteractionTime);
        $d1 = $t1->format("Y-m-d");
        $d2 = $t2->format("Y-m-d");

        return $d1 != $d2;
    }

    public function gameTime(): DateTime
    {
        return $this->convertToGameTime(new DateTime());
    }

    public function convertFromGameTime(
        DateTime $time
    ): DateTime {
        // Game dates are in the distant past, better not use getTimestamp().
        $i = $this->theBeginning->diff($time);

        $seconds = 0;
        $seconds += $i->days * $this->secondsPerGameDay;
        $seconds += $i->h * $this->secondsPerGameHour;
        $seconds += $i->i * $this->secondsPerGameMinute;
        $seconds += $i->s * $this->secondsPerGameSecond;

        $ret = clone($this->adjustedEpoch);
        return $ret->add($this->interval(0, 0, 0, 0, (int) $seconds));
    }

    public function convertToGameTime(
        DateTime $time
    ): DateTime {
        $timeUnix = $time->getTimestamp();
        $epochUnix = $this->adjustedEpoch->getTimestamp();

        $interval = $timeUnix - $epochUnix;

        $years = (int) ($interval / $this->secondsPerGameYear);
        $interval -= $years * $this->secondsPerGameYear;

        $days = (int) ($interval / $this->secondsPerGameDay);
        $interval -= $days * $this->secondsPerGameDay;

        $hours = (int) ($interval / $this->secondsPerGameHour);
        $interval -= $hours * $this->secondsPerGameHour;

        $minutes = (int) ($interval / $this->secondsPerGameMinute);
        $interval -= $minutes * $this->secondsPerGameMinute;

        $seconds = (int) ($interval / $this->secondsPerGameSecond);
        $interval -= $seconds * $this->secondsPerGameSecond;

        $ret = clone($this->theBeginning);
        return $ret->add(
            $this->interval($years, $days, $hours, $minutes, $seconds)
        );
    }

    private function interval(
        int $years,
        int $days,
        int $hours,
        int $minutes,
        int $seconds
    ): \DateInterval {
        return new \DateInterval(
            'P'.$years.'Y'.$days.'DT'.$hours.'H'.$minutes.'M'.$seconds.'S'
        );
    }
}

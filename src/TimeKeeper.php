<?php
declare(strict_types=1);

namespace LotGD\Core;

use DateTime;
use LotGD\Core\Exceptions\ArgumentException;

/**
 * Configurable way to convert back and forth between real time and game time.
 */
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

    private $now;

    /**
     * Construct a TimeKeeper with required configuration.
     * @param DateTime $gameEpoch When in real time is game day 0.
     * @param int $gameOffsetSeconds How many seconds from midnight on the epoch should the first game day start.
     * @param int $gameDaysPerDay How many game days are in one real day.
     */
    public function __construct(DateTime $gameEpoch, int $gameOffsetSeconds, int $gameDaysPerDay)
    {
        $gameEpochCopy = clone($gameEpoch);

        if ($gameOffsetSeconds < 0) {
            $this->adjustedEpoch = $gameEpochCopy->sub(
                $this->interval(0, 0, 0, 0, $gameOffsetSeconds*-1)
            );
        } else {
            $this->adjustedEpoch = $gameEpochCopy->add(
                $this->interval(0, 0, 0, 0, $gameOffsetSeconds)
            );
        }
        
        $this->theBeginning = new DateTime("0000-01-01 UTC");

        $this->secondsPerGameDay = (float) $this->secondsPerDay / $gameDaysPerDay;
        $this->secondsPerGameYear = $this->secondsPerGameDay * 365;
        $this->secondsPerGameHour = $this->secondsPerGameDay / 24;
        $this->secondsPerGameMinute = $this->secondsPerGameHour / 60;
        $this->secondsPerGameSecond = $this->secondsPerGameMinute / 60;

        $this->now = new DateTime();
    }

    /**
     * Changes the "now" state of the TimeKeeper.
     * @param DateTime $dateTime
     */
    public function changeNow(DateTime $dateTime)
    {
        $this->now = $dateTime;
    }

    /**
     * Returns whether a user who is interating with the game now and last
     * interacted at $lastInteractionTime should experience a New Day event.
     * @param DateTime|null $lastInteractionTime
     * @return bool
     */
    public function isNewDay(?DateTime $lastInteractionTime): bool
    {
        if ($lastInteractionTime == null) {
            return true;
        }

        $t1 = $this->getGameTime();
        $t2 = $this->convertToGameTime($lastInteractionTime);
        $d1 = $t1->format("Y-m-d");
        $d2 = $t2->format("Y-m-d");

        return $d1 != $d2;
    }

    /**
     * Return the current game time.
     * @return DateTime
     */
    public function getGameTime(): DateTime
    {
        return $this->convertToGameTime($this->now);
    }

    /**
     * Given a game time, convert it to a real time.
     * @param DateTime $time Game time to convert.
     * @return DateTime Real time corresponding to game time $time.
     */
    public function convertFromGameTime(DateTime $time): DateTime {
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

    /**
     * Given a real time, convert it to a game time.
     * @param DateTime $time Real time to convert.
     * @return DateTime Game time corresponding to real time $time.
     */
    public function convertToGameTime(DateTime $time): DateTime {
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

    /**
     * Convenience method to generate a DateInterval from an exploded date.
     */
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

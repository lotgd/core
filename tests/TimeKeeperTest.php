<?php
declare(strict_types=1);

namespace LotGD\Core\Tests;

use DateTime;
use LotGD\Core\TimeKeeper;

/**
* @backupGlobals disabled
* @backupStaticAttributes disabled
*/
class TimeKeeperTests extends \PHPUnit_Framework_TestCase {
    private $gameEpoch;
    private $gameOffsetSeconds;
    private $gameDaysPerDay;

    public function setUp() {
        $this->gameEpoch = new \DateTime('2015-07-27 00:00:00 PDT');;
        $this->gameOffsetSeconds = 0;
        $this->gameDaysPerDay = 2;
    }

    public function testConvertToBasicConversion() {
        $this->gameDaysPerDay = 1;
        $keeper = new TimeKeeper($this->gameEpoch, new DateTime(), $this->gameOffsetSeconds, $this->gameDaysPerDay);

        $date = new \DateTime('2015-07-27 23:59:59 PDT');
        $converted = $keeper->convertToGameTime($date);
        $this->assertEquals('0000-01-01 23:59:59', $converted->format('Y-m-d H:i:s'));

        $date = new \DateTime('2015-07-27 12:00:00 PDT');
        $converted = $keeper->convertToGameTime($date);
        $this->assertEquals('0000-01-01 12:00:00', $converted->format('Y-m-d H:i:s'));
    }

    public function testConvertToRespectsGameDaysPerDayUpperBound() {
        $date = new \DateTime('2015-07-27 05:59:59 PDT');

        $this->gameDaysPerDay = 4;
        $keeper = new TimeKeeper($this->gameEpoch, new DateTime(), $this->gameOffsetSeconds, $this->gameDaysPerDay);

        $converted = $keeper->convertToGameTime($date);
        $this->assertEquals('0000-01-01', $converted->format('Y-m-d'));
    }

    public function testConvertToRespectsGameDaysPerDayNextDay() {
        $date = new \DateTime('2015-07-27 06:00:00 PDT');

        $this->gameDaysPerDay = 4;
        $keeper = new TimeKeeper($this->gameEpoch, new DateTime(), $this->gameOffsetSeconds, $this->gameDaysPerDay);

        $converted = $keeper->convertToGameTime($date);
        $this->assertEquals('0000-01-02', $converted->format('Y-m-d'));
    }

    public function testConvertToRespectsGameDaysPerDayNextDayUpperBound() {
        $date = new \DateTime('2015-07-27 11:59:59 PDT');

        $this->gameDaysPerDay = 4;
        $keeper = new TimeKeeper($this->gameEpoch, new DateTime(), $this->gameOffsetSeconds, $this->gameDaysPerDay);

        $converted = $keeper->convertToGameTime($date);
        $this->assertEquals('0000-01-02', $converted->format('Y-m-d'));
    }

    public function testIfIsNewDayReturnsTrueWithNullAsLastInteractionTime() {
        $keeper = new TimeKeeper($this->gameEpoch, new DateTime(), $this->gameOffsetSeconds, 4);

        $this->assertTrue($keeper->isNewDay(null));
    }

    public function testIfIsNewDayReturnsFalseIfLastInteractionTimeWasJustRecently()
    {
        // game days per day: 4, each day 6h.
        $keeper = new TimeKeeper($this->gameEpoch, new DateTime("2017-01-02 11:59:59"), 3600*5, 4);

        $time1 = new \DateTime("2017-01-02 06:00:00");
        $time2 = new \DateTime("2017-01-02 11:59:59");
        $time3 = new \DateTime("2017-01-02 09:00:00");
        $time4 = new \DateTime("2017-01-02 09:59:30");

        $this->assertFalse($keeper->isNewDay($time1));
        $this->assertFalse($keeper->isNewDay($time2));
        $this->assertFalse($keeper->isNewDay($time3));
        $this->assertFalse($keeper->isNewDay($time4));
    }

    public function testIfIsNewDayReturnsFalseIfLastInteractionTimeWasOnLastGameDay()
    {
        // game days per day: 4, each day 6h.
        // interestingly, it looks like new game day starts 01:00:00?
        $keeper = new TimeKeeper($this->gameEpoch, new DateTime("2017-01-02 12:00:00"), 3600*5, 4);

        $time1 = new \DateTime("2017-01-02 06:00:00");
        $time2 = new \DateTime("2017-01-02 11:59:59");
        $time3 = new \DateTime("2017-01-02 09:00:00");
        $time4 = new \DateTime("2017-01-02 09:59:30");

        $this->assertTrue($keeper->isNewDay($time1));
        $this->assertTrue($keeper->isNewDay($time2));
        $this->assertTrue($keeper->isNewDay($time3));
        $this->assertTrue($keeper->isNewDay($time4));
    }

    public function testConvertToRespectsGameOffset() {
        $date = new \DateTime('2015-07-27 01:01:15 PDT');

        $this->gameOffsetSeconds = 60*60;
        $this->gameDaysPerDay = 1;
        $keeper = new TimeKeeper($this->gameEpoch, new DateTime(), $this->gameOffsetSeconds, $this->gameDaysPerDay);

        $converted = $keeper->convertToGameTime($date);
        $this->assertEquals('0000-01-01 00:01:15', $converted->format('Y-m-d H:i:s'));
    }

    public function testConvertToRespectsGameOffsetUpperBound() {
        $date = new \DateTime('2015-07-28 00:59:59 PDT');

        $this->gameOffsetSeconds = 60*60;
        $this->gameDaysPerDay = 1;
        $keeper = new TimeKeeper($this->gameEpoch, new DateTime(), $this->gameOffsetSeconds, $this->gameDaysPerDay);

        $converted = $keeper->convertToGameTime($date);
        $this->assertEquals('0000-01-01 23:59:59', $converted->format('Y-m-d H:i:s'));
    }

    public function testConvertToRespectsGameOffsetNextDay() {
        $date = new \DateTime('2015-07-28 01:00:00 PDT');

        $this->gameOffsetSeconds = 60*60;
        $this->gameDaysPerDay = 1;
        $keeper = new TimeKeeper($this->gameEpoch, new DateTime(), $this->gameOffsetSeconds, $this->gameDaysPerDay);

        $converted = $keeper->convertToGameTime($date);
        $this->assertEquals('0000-01-02', $converted->format('Y-m-d'));
    }

    public function testConvertToRespectsGameOffsetNextDayUpperBound() {
        $date = new \DateTime('2015-07-29 00:59:59 PDT');

        $this->gameOffsetSeconds = 60*60;
        $this->gameDaysPerDay = 1;
        $keeper = new TimeKeeper($this->gameEpoch, new DateTime(), $this->gameOffsetSeconds, $this->gameDaysPerDay);

        $converted = $keeper->convertToGameTime($date);
        $this->assertEquals('0000-01-02', $converted->format('Y-m-d'));
    }

    public function testConvertFromBasicConversion() {
        $date = new \DateTime('0000-01-02 00:00:00 UTC');

        $this->gameDaysPerDay = 1;
        $keeper = new TimeKeeper($this->gameEpoch, new DateTime(), $this->gameOffsetSeconds, $this->gameDaysPerDay);

        $converted = $keeper->convertFromGameTime($date);
        $this->assertEquals("2015-07-28", $converted->format('Y-m-d'));
    }

    public function testConvertFromRespectsGameOffsetNextDay() {
        $epoch = new \DateTime('2015-07-27 00:00:00 PDT');
        $date = new \DateTime('0000-01-02 23:59:59 UTC');

        $this->gameEpoch = $epoch;
        $this->gameOffsetSeconds = 60*60;
        $this->gameDaysPerDay = 1;
        $keeper = new TimeKeeper($this->gameEpoch, new DateTime(), $this->gameOffsetSeconds, $this->gameDaysPerDay);

        $converted = $keeper->convertFromGameTime($date);
        $this->assertEquals("2015-07-29 00:59:59", $converted->format('Y-m-d H:i:s'));
    }

    public function testConvertFromRespectsGameDaysPerDayNextDay() {
        $epoch = new \DateTime('2015-07-27 00:00:00 PDT');
        $date = new \DateTime('0000-01-02 23:59:59 UTC');

        $this->gameEpoch = $epoch;
        $this->gameDaysPerDay = 4;
        $keeper = new TimeKeeper($this->gameEpoch, new DateTime(), $this->gameOffsetSeconds, $this->gameDaysPerDay);

        $converted = $keeper->convertFromGameTime($date);
        $this->assertEquals("2015-07-27 11:59:59", $converted->format('Y-m-d H:i:s'));
    }

    public function testGameTimeSanity() {
        $keeper = new TimeKeeper($this->gameEpoch, new DateTime(), $this->gameOffsetSeconds, $this->gameDaysPerDay);
        $this->assertNotNull($keeper->getGameTime());
    }
}

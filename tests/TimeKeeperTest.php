<?php
declare(strict_types=1);

namespace LotGD\Core\Tests;

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
    $keeper = new TimeKeeper($this->gameEpoch, $this->gameOffsetSeconds, $this->gameDaysPerDay);

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
    $keeper = new TimeKeeper($this->gameEpoch, $this->gameOffsetSeconds, $this->gameDaysPerDay);

    $converted = $keeper->convertToGameTime($date);
    $this->assertEquals('0000-01-01', $converted->format('Y-m-d'));
  }

  public function testConvertToRespectsGameDaysPerDayNextDay() {
    $date = new \DateTime('2015-07-27 06:00:00 PDT');

    $this->gameDaysPerDay = 4;
    $keeper = new TimeKeeper($this->gameEpoch, $this->gameOffsetSeconds, $this->gameDaysPerDay);

    $converted = $keeper->convertToGameTime($date);
    $this->assertEquals('0000-01-02', $converted->format('Y-m-d'));
  }

  public function testConvertToRespectsGameDaysPerDayNextDayUpperBound() {
    $date = new \DateTime('2015-07-27 11:59:59 PDT');

    $this->gameDaysPerDay = 4;
    $keeper = new TimeKeeper($this->gameEpoch, $this->gameOffsetSeconds, $this->gameDaysPerDay);

    $converted = $keeper->convertToGameTime($date);
    $this->assertEquals('0000-01-02', $converted->format('Y-m-d'));
  }

  public function testDetectsNewDay() {
  }

  public function testConvertToRespectsGameOffset() {
    $date = new \DateTime('2015-07-27 01:01:15 PDT');

    $this->gameOffsetSeconds = 60*60;
    $this->gameDaysPerDay = 1;
    $keeper = new TimeKeeper($this->gameEpoch, $this->gameOffsetSeconds, $this->gameDaysPerDay);

    $converted = $keeper->convertToGameTime($date);
    $this->assertEquals('0000-01-01 00:01:15', $converted->format('Y-m-d H:i:s'));
  }

  public function testConvertToRespectsGameOffsetUpperBound() {
    $date = new \DateTime('2015-07-28 00:59:59 PDT');

    $this->gameOffsetSeconds = 60*60;
    $this->gameDaysPerDay = 1;
    $keeper = new TimeKeeper($this->gameEpoch, $this->gameOffsetSeconds, $this->gameDaysPerDay);

    $converted = $keeper->convertToGameTime($date);
    $this->assertEquals('0000-01-01 23:59:59', $converted->format('Y-m-d H:i:s'));
  }

  public function testConvertToRespectsGameOffsetNextDay() {
    $date = new \DateTime('2015-07-28 01:00:00 PDT');

    $this->gameOffsetSeconds = 60*60;
    $this->gameDaysPerDay = 1;
    $keeper = new TimeKeeper($this->gameEpoch, $this->gameOffsetSeconds, $this->gameDaysPerDay);

    $converted = $keeper->convertToGameTime($date);
    $this->assertEquals('0000-01-02', $converted->format('Y-m-d'));
  }

  public function testConvertToRespectsGameOffsetNextDayUpperBound() {
    $date = new \DateTime('2015-07-29 00:59:59 PDT');

    $this->gameOffsetSeconds = 60*60;
    $this->gameDaysPerDay = 1;
    $keeper = new TimeKeeper($this->gameEpoch, $this->gameOffsetSeconds, $this->gameDaysPerDay);

    $converted = $keeper->convertToGameTime($date);
    $this->assertEquals('0000-01-02', $converted->format('Y-m-d'));
  }

  public function testConvertFromBasicConversion() {
    $date = new \DateTime('0000-01-02 00:00:00 UTC');

    $this->gameDaysPerDay = 1;
    $keeper = new TimeKeeper($this->gameEpoch, $this->gameOffsetSeconds, $this->gameDaysPerDay);

    $converted = $keeper->convertFromGameTime($date);
    $this->assertEquals("2015-07-28", $converted->format('Y-m-d'));
  }

  public function testConvertFromRespectsGameOffsetNextDay() {
    $epoch = new \DateTime('2015-07-27 00:00:00 PDT');
    $date = new \DateTime('0000-01-02 23:59:59 UTC');

    $this->gameEpoch = $epoch;
    $this->gameOffsetSeconds = 60*60;
    $this->gameDaysPerDay = 1;
    $keeper = new TimeKeeper($this->gameEpoch, $this->gameOffsetSeconds, $this->gameDaysPerDay);

    $converted = $keeper->convertFromGameTime($date);
    $this->assertEquals("2015-07-29 00:59:59", $converted->format('Y-m-d H:i:s'));
  }

  public function testConvertFromRespectsGameDaysPerDayNextDay() {
    $epoch = new \DateTime('2015-07-27 00:00:00 PDT');
    $date = new \DateTime('0000-01-02 23:59:59 UTC');

    $this->gameEpoch = $epoch;
    $this->gameDaysPerDay = 4;
    $keeper = new TimeKeeper($this->gameEpoch, $this->gameOffsetSeconds, $this->gameDaysPerDay);

    $converted = $keeper->convertFromGameTime($date);
    $this->assertEquals("2015-07-27 11:59:59", $converted->format('Y-m-d H:i:s'));
  }

  public function testGameTimeSanity() {
    $keeper = new TimeKeeper($this->gameEpoch, $this->gameOffsetSeconds, $this->gameDaysPerDay);
    $this->assertNotNull($keeper->gameTime());
  }
}

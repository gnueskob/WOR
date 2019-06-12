<?php

namespace lsb\Libs;

use DateTime;
use DateTimeZone;
use DateInterval;
use Exception;

class Timezone extends DateTime
{
    const FORMAT = 'Y-m-d H:i:s';
    private static $unitTime = null;

    public function __construct(string $timezone = 'UTC', $time = 'now')
    {
        date_default_timezone_set("UTC");
        parent::__construct($time, new DateTimeZone('UTC'));
        $this->setTimezone(new DateTimeZone($timezone));
    }

    public function modifyDate(string $modify): string
    {
        $this->modify($modify);
        return $this->getTime();
    }

    public function addDate(string $dateInterval): Timezone
    {
        $interval = DateInterval::createFromDateString($dateInterval);
        $this->add($interval);
        return $this;
    }

    public function subDate(string $dateInterval): Timezone
    {
        $interval = DateInterval::createFromDateString($dateInterval);
        $this->sub($interval);
        return $this;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getUTC(): string
    {
        $currentTime = $this->format(self::FORMAT);
        $timezone = $this->getTimezone();
        $date = new DateTime($currentTime, $timezone);
        $date->setTimezone(new DateTimeZone('UTC'));
        return $date->format(self::FORMAT);
    }

    public function getTime(): string
    {
        return $this->format(self::FORMAT);
    }

    /**
     * @param string $timezone
     * @return string
     * @throws Exception
     */
    public static function getNow(string $timezone): string
    {
        return (new DateTime('now', new DateTimeZone($timezone)))->format(self::FORMAT);
    }

    /**
     * @return string
     * @throws Exception
     */
    public static function getNowUTC(): string
    {
        return (new DateTime('now', new DateTimeZone('UTC')))->format(self::FORMAT);
    }

    public static function getCompleteTime(int $needUnitTime)
    {
        if (is_null(self::$unitTime)) {
            self::$unitTime = Plan::getData(PLAN_UNIT, UNIT_TIME)['value'];
        }
        $sec = self::$unitTime * $needUnitTime;
        return (new Timezone())->addDate("{$sec} seconds")->getTime();
    }
}

<?php

namespace lsb\Libs;

use DateTime;
use DateTimeZone;
use Exception;

class Timezone extends DateTime
{
    const FORMAT = 'Y-m-d H:i:s';

    public function __construct(string $timezone, $time = 'now')
    {
        date_default_timezone_set("UTC");
        parent::__construct($time, new DateTimeZone('UTC'));
        $this->setTimezone(new DateTimeZone($timezone));
    }

    public function modify($modify): string
    {
        parent::modify($modify);
        return $this->getTime();
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
}

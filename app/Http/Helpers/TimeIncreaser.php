<?php

namespace App\Http\Helpers;

use DateTime;
use Exception;

class TimeIncreaser
{
    /**
     * @throws Exception
     */
    public static function addHours(DateTime $dt, int $hours): DateTime
    {
        return $dt->add(new \DateInterval(
            config('formats.interval_time_start') .
            $hours .
            config('formats.hour_format')
        ));
    }

    /**
     * @throws Exception
     */
    public static function addDays(DateTime $dt): void
    {
        if (self::isFriday($dt)) {
            $dt->add(new \DateInterval(config('formats.interval_three_days')));
        } else {
            $dt->add(new \DateInterval(config('formats.interval_one_day')));
        }
    }

    /**
     * @throws Exception
     */
    private static function isFriday(DateTime $dt): bool
    {
        if ($dt->format(config('formats.week_day_format')) == config('formats.number_of_friday')) {
            return true;
        }

        return false;
    }

}

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
        return $dt->add(new \DateInterval('PT' . $hours . 'H'));
    }

    /**
     * @throws Exception
     */
    public static function addDays(DateTime $dt): void
    {
        if (self::isFriday($dt)) {
            $dt->add(new \DateInterval('P3D'));
        } else {
            $dt->add(new \DateInterval('P1D'));
        }
    }

    /**
     * @throws Exception
     */
    private static function isFriday(DateTime $dt): bool
    {
        if ($dt->format('N') == 5) {
//        if ($dt->format($this->dateCalcContr::WEEK_DAY_FORMAT) == self::NUMBER_OF_FRIDAY) {
            return true;
        }

        return false;
    }

}

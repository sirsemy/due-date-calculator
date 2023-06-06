<?php

namespace App\Http\Executors;

use App\Exceptions\CalculationException;
use App\Http\Controllers\DateCalculateController;
use App\Http\Helpers\TimeIncreaser;
use DateTime;
use Exception;

abstract class TimeDemandCalculator
{
    protected const NUMBER_OF_FRIDAY = 5;

    protected DateCalculateController $dateCalcContr;
    protected DateTime $calculateDate;

    /**
     * @throws Exception
     */
    public function __construct(DateCalculateController $dc)
    {
        $this->dateCalcContr = $dc;
    }

    /**
     * @throws CalculationException
     * @throws Exception
     */
    abstract public function runCalculation(): void;

    /**
     * @throws Exception
     */
    public static function canProblemSolvableSameDay(DateCalculateController $dc): bool
    {
        $futureDate = DateTime::createFromInterface($dc->getSubmittedDateTime());
        $finishTime = DateTime::createFromInterface($dc->getSubmittedDateTime());

        $futureDate = TimeIncreaser::addHours($futureDate, $dc->getEstimatedTime());
        $finishTime->setTime($dc::FINISHING_WORK_HOUR, 0);

        if ($futureDate > $finishTime) {
            return false;
        }

        return true;
    }
}

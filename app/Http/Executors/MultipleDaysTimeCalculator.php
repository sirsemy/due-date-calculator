<?php

namespace App\Http\Helpers;

use App\Exceptions\CalculationException;
use App\Exceptions\ExceptionCases;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Log;

class MultipleDaysTimeCalculator extends TimeDemandCalculator
{
    public function runCalculation(): void
    {
        try {
            $calcDate = $this->calculateMultipleWorkingDays();
            $this->dateCalcContr->setCalculatedDate($calcDate);
        } catch (Exception $e) {
            Log::error('DateInterval not worked during calculate multiple working days. Error message: ' .
                $e->getMessage());
            throw new CalculationException(ExceptionCases::CalculationError);
        }
    }

    /**
     * @throws Exception
     */
    private function calculateMultipleWorkingDays(): DateTime
    {
        $estimatedTime = $this->dateCalcContr->getEstimatedTime();
        $this->calculateDate = DateTime::createFromInterface($this->dateCalcContr->getSubmittedDateTime());
        $workDaysAmount = intdiv($estimatedTime, $this->dateCalcContr::WORKING_HOURS_PER_DAY);

        while ($workDaysAmount > 0) {
            TimeIncreaser::addDays($this->calculateDate);
            $workDaysAmount--;
        }

        $this->setRemainHours($estimatedTime);

        return $this->calculateDate;
    }

    /**
     * @throws Exception
     */
    private function setRemainHours(int $estimatedTime): void
    {
        $remainHours = $estimatedTime % $this->dateCalcContr::WORKING_HOURS_PER_DAY;

        $this->dateCalcContr->setSubmittedDateTime(DateTime::createFromInterface($this->calculateDate));
        $this->dateCalcContr->setEstimatedTime($remainHours);

        if ($remainHours && TimeDemandCalculator::canProblemSolvableSameDay($this->dateCalcContr)) {
            TimeIncreaser::addHours($this->calculateDate, $remainHours);
        } else {
            $this->setRemainHoursOnNextDay($remainHours);
        }
    }

    /**
     * @throws Exception
     */
    private function setRemainHoursOnNextDay(int $remainHours): void
    {
        $submittedHour = (int)$this->dateCalcContr->getSubmittedDateTime()->format('H');
        $submittedMinutes = (int)$this->dateCalcContr->getSubmittedDateTime()->format('i');

        TimeIncreaser::addDays($this->calculateDate);

        $this->calculateDate->setTime($this->dateCalcContr::STARTING_WORK_HOUR, $submittedMinutes);

        $remainHours += $submittedHour - $this->dateCalcContr::FINISHING_WORK_HOUR;

        if (!empty($remainHours) && $remainHours > 0) {
            TimeIncreaser::addHours($this->calculateDate, $remainHours);
        }
    }
}

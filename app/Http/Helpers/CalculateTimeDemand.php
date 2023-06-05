<?php

namespace App\Http\Helpers;

use App\Exceptions\CalculationException;
use App\Exceptions\ExceptionCase;
use App\Http\Controllers\DateCalculateController;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Log;

class CalculateTimeDemand
{
    private const NUMBER_OF_FRIDAY = 5;

    private DateCalculateController $dateCalcContr;
    private DateTime $calculateDate;

    /**
     * @throws CalculationException
     */
    public function __construct(DateCalculateController $dc)
    {
        $this->dateCalcContr = $dc;
        $this->runCalculation();
    }

    /**
     * @throws CalculationException
     * @throws Exception
     */
    private function runCalculation(): void
    {
        if ($this->canProblemSolvableSameDay()) {
            $this->calculateSameDayTime();
        } else {
            $this->calculateMultipleDaysTime();
        }
    }

    /**
     * @throws CalculationException
     */
    private function calculateSameDayTime(): void
    {
        try {
            $calcDate = $this->dateCalcContr->getSubmittedDateTime()
                ->add(new \DateInterval('PT' . $this->dateCalcContr->getEstimatedTime() . 'H'));
            $this->dateCalcContr->setCalculatedDate($calcDate);
        } catch (Exception $e) {
            Log::error('DateInterval not worked during calculate single working day. Error message: ' .
                $e->getMessage());
            throw new CalculationException(ExceptionCase::CalculationError);
        }
    }

    /**
     * @throws CalculationException
     */
    private function calculateMultipleDaysTime(): void
    {
        try {
            $calcDate = $this->calculateMultipleWorkingDays();
            $this->dateCalcContr->setCalculatedDate($calcDate);
        } catch (Exception $e) {
            Log::error('DateInterval not worked during calculate multiple working days. Error message: ' .
                $e->getMessage());
            throw new CalculationException(ExceptionCase::CalculationError);
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
            $this->addDays();
            $workDaysAmount--;
        }

        $this->setRemainHours($estimatedTime);

        return $this->calculateDate;
    }

    /**
     * @throws Exception
     */
    private function addDays(): void
    {
        if ($this->isFriday()) {
            $this->calculateDate->add(new \DateInterval('P3D'));
        } else {
            $this->calculateDate->add(new \DateInterval('P1D'));
        }
    }

    /**
     * @throws Exception
     */
    private function isFriday(): bool
    {
        if ($this->calculateDate->format($this->dateCalcContr::WEEK_DAY_FORMAT) == self::NUMBER_OF_FRIDAY) {
            return true;
        }

        return false;
    }

    /**
     * @throws Exception
     */
    private function canProblemSolvableSameDay(): bool
    {
        $futureDate = DateTime::createFromInterface($this->dateCalcContr->getSubmittedDateTime());
        $finishTime = DateTime::createFromInterface($this->dateCalcContr->getSubmittedDateTime());

        $futureDate = $this->addHours($futureDate, $this->dateCalcContr->getEstimatedTime());
        $finishTime->setTime($this->dateCalcContr::FINISHING_WORK_HOUR, 0);

        if ($futureDate > $finishTime) {
            return false;
        }

        return true;
    }

    /**
     * @throws Exception
     */
    private function addHours(DateTime $dt, int $hours): DateTime
    {
        return $dt->add(new \DateInterval('PT' . $hours . 'H'));
    }

    /**
     * @throws Exception
     */
    private function setRemainHours(int $estimatedTime): void
    {
        $remainHours = $estimatedTime % $this->dateCalcContr::WORKING_HOURS_PER_DAY;

        $this->dateCalcContr->setSubmittedDateTime(DateTime::createFromInterface($this->calculateDate));
        $this->dateCalcContr->setEstimatedTime($remainHours);

        if ($remainHours && $this->canProblemSolvableSameDay()) {
            $this->addHours($this->calculateDate, $remainHours);
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

        $this->addDays();

        $this->calculateDate->setTime($this->dateCalcContr::STARTING_WORK_HOUR, $submittedMinutes);

        $remainHours += $submittedHour - $this->dateCalcContr::FINISHING_WORK_HOUR;

        if (!empty($remainHours) && $remainHours > 0) {
            $this->addHours($this->calculateDate, $remainHours);
        }
    }
}

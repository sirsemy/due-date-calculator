<?php

namespace App\Http\Helpers;

use App\Exceptions\CalculationException;
use App\Exceptions\ExceptionCase;
use App\Http\Controllers\DateCalculateController;
use DateTime;
use DateTimeImmutable;
use Exception;
use Illuminate\Support\Facades\Log;

class CalculateTimeDemand
{
    private DateCalculateController $calcContr;

    public function __construct(DateCalculateController $dc)
    {
        $this->calcContr = $dc;
    }

    /**
     * @throws CalculationException
     */
    public function calculateSameDayTime(): void
    {
        try {
            $calcDate = $this->calcContr->getSubmittedDateTime()
                ->add(new \DateInterval('PT' . $this->calcContr->getEstimatedTime() . 'H'));
            $this->calcContr->setCalculatedDate($calcDate);
        } catch (Exception $e) {
            Log::error('DateInterval not worked during calculate multiple working days. Error message: ' .
                $e->getMessage());
            throw new CalculationException(ExceptionCase::CalculationError);
        }
    }

    /**
     * @throws CalculationException
     */
    public function calculateMultipleDaysTime(): void
    {
        try {
            $calcDate = $this->calculateMultipleWorkingDays();
            $this->calcContr->setCalculatedDate($calcDate);
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
        $estimatedTime = $this->calcContr->getEstimatedTime();
        $calculateDate = (new DateTime())::createFromImmutable($this->calcContr->getSubmittedDateTime());
        $submittedHour = (int)$this->calcContr->getSubmittedDateTime()->format('H');
        $submittedMinutes = (int)$this->calcContr->getSubmittedDateTime()->format('i');
        $workDaysAmount = intdiv($estimatedTime, $this->calcContr::WORKING_HOURS_PER_DAY);
        $remainHour = $estimatedTime % $this->calcContr::WORKING_HOURS_PER_DAY;

        while ($workDaysAmount > 0) {
            if ($this->isNextDayIsWeekendDay($calculateDate)) {
                $calculateDate->add(new \DateInterval('P3D'));
                $workDaysAmount--;
                continue;
            }

            $calculateDate->add(new \DateInterval('P1D'));
            $workDaysAmount--;
        }

        $this->calcContr->setSubmittedDateTime(
            (new DateTimeImmutable())::createFromMutable($calculateDate)
        );
        $this->calcContr->setEstimatedTime($remainHour);

        if ($remainHour && $this->calcContr->canProblemSolvableSameDay()) {
            return $calculateDate->add(new \DateInterval('PT' . $remainHour . 'H'));
        }

        if ($this->isNextDayIsWeekendDay($calculateDate)) {
            $calculateDate->add(new \DateInterval('P3D'))->setTime($this->calcContr::STARTING_WORK_HOUR, $submittedMinutes);
        } else {
            $calculateDate->add(new \DateInterval('P1D'))->setTime($this->calcContr::STARTING_WORK_HOUR, $submittedMinutes);
        }

        $remainHour = ($submittedHour + $remainHour) - $this->calcContr::FINISHING_WORK_HOUR;

        if (empty($remainHour) || $remainHour < 0) {
            return $calculateDate;
        }

        return $calculateDate->add(new \DateInterval('PT' . $remainHour . 'H'));
    }

    /**
     * @throws Exception
     */
    private function isNextDayIsWeekendDay(DateTime $dt): bool
    {
        if ($dt->format($this->calcContr::WEEK_DAY_FORMAT) >= 5) {
            return true;
        }

        return false;
    }
}

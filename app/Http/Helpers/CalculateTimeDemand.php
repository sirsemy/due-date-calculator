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
    private const NUMBER_OF_FRIDAY = 5;

    private DateCalculateController $dateCalcContr;

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
            Log::error('DateInterval not worked during calculate multiple working days. Error message: ' .
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
        $calculateDate = (new DateTime())::createFromImmutable($this->dateCalcContr->getSubmittedDateTime());
        $submittedHour = (int)$this->dateCalcContr->getSubmittedDateTime()->format('H');
        $submittedMinutes = (int)$this->dateCalcContr->getSubmittedDateTime()->format('i');
        $workDaysAmount = intdiv($estimatedTime, $this->dateCalcContr::WORKING_HOURS_PER_DAY);
        $remainHour = $estimatedTime % $this->dateCalcContr::WORKING_HOURS_PER_DAY;

        while ($workDaysAmount > 0) {
            if ($this->isFriday($calculateDate)) {
                $calculateDate->add(new \DateInterval('P3D'));
                $workDaysAmount--;
                continue;
            }

            $calculateDate->add(new \DateInterval('P1D'));
            $workDaysAmount--;
        }

        $this->dateCalcContr->setSubmittedDateTime(
            (new DateTimeImmutable())::createFromMutable($calculateDate)
        );
        $this->dateCalcContr->setEstimatedTime($remainHour);

        if ($remainHour && $this->canProblemSolvableSameDay()) {
            return $calculateDate->add(new \DateInterval('PT' . $remainHour . 'H'));
        }

        if ($this->isFriday($calculateDate)) {
            $calculateDate->add(new \DateInterval('P3D'))
                ->setTime($this->dateCalcContr::STARTING_WORK_HOUR, $submittedMinutes);
        } else {
            $calculateDate->add(new \DateInterval('P1D'))
            ->setTime($this->dateCalcContr::STARTING_WORK_HOUR, $submittedMinutes);
        }

        $remainHour = ($submittedHour + $remainHour) - $this->dateCalcContr::FINISHING_WORK_HOUR;

        if (empty($remainHour) || $remainHour < 0) {
            return $calculateDate;
        }

        return $calculateDate->add(new \DateInterval('PT' . $remainHour . 'H'));
    }

    /**
     * @throws Exception
     */
    private function isFriday(DateTime $dt): bool
    {
        if ($dt->format($this->dateCalcContr::WEEK_DAY_FORMAT) == self::NUMBER_OF_FRIDAY) {
            return true;
        }

        return false;
    }

    /**
     * @throws Exception
     */
    private function canProblemSolvableSameDay(): bool
    {
        $summaDate = $this->dateCalcContr->getSubmittedDateTime()
            ->add(new \DateInterval('PT' . $this->dateCalcContr->getEstimatedTime() . 'H'));
        $finishTime = $this->dateCalcContr->getSubmittedDateTime()
            ->setTime($this->dateCalcContr::FINISHING_WORK_HOUR, 0);

        if ($summaDate > $finishTime) {
            return false;
        }

        return true;
    }
}

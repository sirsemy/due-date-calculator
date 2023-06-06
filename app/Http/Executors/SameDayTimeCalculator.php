<?php

namespace App\Http\Executors;

use App\Exceptions\CalculationException;
use App\Exceptions\ExceptionCases;
use Exception;
use Illuminate\Support\Facades\Log;

class SameDayTimeCalculator extends TimeDemandCalculator
{
    /**
     * @throws CalculationException
     */
    public function runCalculation(): void
    {
        try {
            $calcDate = $this->dateCalcContr->getSubmittedDateTime()
                ->add(new \DateInterval(
                    config('formats.interval_time_start') .
                    $this->dateCalcContr->getEstimatedTime() .
                    config('formats.hour_format')
            ));
            $this->dateCalcContr->setCalculatedDate($calcDate);
        } catch (Exception $e) {
            Log::error('DateInterval not worked during calculate single working day. Error message: ' .
                $e->getMessage());
            throw new CalculationException(ExceptionCases::CalculationError);
        }
    }
}

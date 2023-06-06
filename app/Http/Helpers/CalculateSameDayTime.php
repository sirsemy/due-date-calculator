<?php

namespace App\Http\Helpers;

use App\Exceptions\CalculationException;
use App\Exceptions\ExceptionCase;
use Exception;
use Illuminate\Support\Facades\Log;

class CalculateSameDayTime extends CalculateTimeDemand
{
    /**
     * @throws CalculationException
     */
    public function runCalculation(): void
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
}

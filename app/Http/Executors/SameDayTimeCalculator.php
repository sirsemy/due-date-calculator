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
                ->add(new \DateInterval('PT' . $this->dateCalcContr->getEstimatedTime() . 'H'));
            $this->dateCalcContr->setCalculatedDate($calcDate);
        } catch (Exception $e) {
            Log::error('DateInterval not worked during calculate single working day. Error message: ' .
                $e->getMessage());
            throw new CalculationException(ExceptionCases::CalculationError);
        }
    }
}

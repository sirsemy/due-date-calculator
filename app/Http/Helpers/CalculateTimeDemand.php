<?php

namespace App\Http\Helpers;

use App\Exceptions\CalculationException;
use App\Exceptions\ExceptionCase;
use App\Http\Controllers\DateCalculateController;
use Illuminate\Support\Facades\Log;

class CalculateTimeDemand
{
    /**
     * @throws CalculationException
     */
    public static function calculateSameDayTime(DateCalculateController $dc): void
    {
        try {
            $calcDate = $dc->getSubmittedDateTime()->add(new \DateInterval('PT' . $dc->getTurnaroundTime() . 'H'));
            $dc->setCalculatedDate($calcDate);
        } catch (\Exception $e) {
            Log::error('DateInterval not worked during calculate multiple working days. Error message: ' .
                $e->getMessage());
            throw new CalculationException(ExceptionCase::CalculationError);
        }
    }

    /**
     * @throws CalculationException
     */
    public static function calculateMultipleDaysTime(DateCalculateController $dc): void
    {
        try {
            $calcDate = $dc->calculateMultipleWorkingDays($dc->getSubmittedDateTime(), $dc->getTurnaroundTime());
            $dc->setCalculatedDate($calcDate);
        } catch (\Exception $e) {
            Log::error('DateInterval not worked during calculate multiple working days. Error message: ' .
                $e->getMessage());
            throw new CalculationException(ExceptionCase::CalculationError);
        }
    }
}

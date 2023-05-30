<?php

namespace App\Http;

use App\Http\Controllers\DateCalculateController;
use Illuminate\Support\Facades\Validator;

class RouteParameterValidator
{
    /**
     * @param DateCalculateController $dc
     */
    public static function setParameterValidationResult(DateCalculateController $dc): void
    {
         $validatorResult = Validator::make($dc->getContollerRequest()->all(), [
            'submit_time' => 'required|date|date_format:Y-m-d H:i:s',
            'turnaround_time' => 'required|integer|min:1',
        ]);

         $dc->setValidator($validatorResult);
    }
}

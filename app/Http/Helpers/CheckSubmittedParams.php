<?php

namespace App\Http\Helpers;

use App\Exceptions\CalculationException;
use App\Exceptions\ExceptionCases;
use App\Http\Controllers\DateCalculateController;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CheckSubmittedParams
{
    private DateCalculateController $dateCalcContr;

    public function __construct(DateCalculateController $dc)
    {
        $this->dateCalcContr = $dc;
    }

    /**
     * @throws ValidationException
     */
    public function validateParameters(Request $request): void
    {
        $validator = Validator::make($request->all(), [
            'submit_time' => 'required|date|date_format:'.config('formats.date_time_format'),
            'estimated_time' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * @throws CalculationException
     */
    public function checkProblemReportedOnWorkingDays(): void
    {
        $examineDay = (int)$this->dateCalcContr->getSubmittedDateTime()->format(config('formats.week_day_format'));

        if ($examineDay >= config('formats.number_of_saturday')) {
            throw new CalculationException(ExceptionCases::WeekendReport);
        }
    }

    /**
     * @throws CalculationException
     */
    public function checkProblemReportedDuringWorkingHours(): void
    {
        $submittedTime = $this->dateCalcContr->getSubmittedDateTime()->format(config('formats.hour_minute_format'));

        $startTime = (new DateTime())->setTime(config('formats.starting_work_hour'), 0)
            ->format(config('formats.hour_minute_format'));
        $finishTime = (new DateTime())->setTime(config('formats.finishing_work_hour'), 0)
            ->format(config('formats.hour_minute_format'));

        if ($submittedTime < $startTime || $submittedTime > $finishTime) {
            throw new CalculationException(ExceptionCases::OutOfWorkingHours);
        }
    }
}

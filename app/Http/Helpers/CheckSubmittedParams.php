<?php

namespace App\Http\Helpers;

use App\Exceptions\CalculationException;
use App\Exceptions\ExceptionCase;
use App\Http\Controllers\DateCalculateController;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CheckSubmittedParams
{
    private const NUMBER_OF_SATURDAY = 6;
    private const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    public const HOUR_MINUTE_FORMAT = 'H:i';
    public const WEEK_DAY_FORMAT = 'N';
    public const STARTING_WORK_HOUR = 9;
    public const FINISHING_WORK_HOUR = 17;

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
            'submit_time' => 'required|date|date_format:'.self::DATE_TIME_FORMAT,
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
        $examineDay = (int)$this->dateCalcContr->getSubmittedDateTime()->format(self::WEEK_DAY_FORMAT);

        if ($examineDay >= self::NUMBER_OF_SATURDAY) {
            throw new CalculationException(ExceptionCase::WeekendReport);
        }
    }

    /**
     * @throws CalculationException
     */
    public function checkProblemReportedDuringWorkingHours(): void
    {
        $submittedTime = $this->dateCalcContr->getSubmittedDateTime()->format(self::HOUR_MINUTE_FORMAT);

        $startTime = (new DateTime())->setTime(self::STARTING_WORK_HOUR, 0)
            ->format(self::HOUR_MINUTE_FORMAT);
        $finishTime = (new DateTime())->setTime(self::FINISHING_WORK_HOUR, 0)
            ->format(self::HOUR_MINUTE_FORMAT);

        if ($submittedTime < $startTime || $submittedTime > $finishTime) {
            throw new CalculationException(ExceptionCase::OutOfWorkingHours);
        }
    }
}

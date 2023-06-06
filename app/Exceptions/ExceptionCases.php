<?php

namespace App\Exceptions;

enum ExceptionCases
{
    case WeekendReport;
    case OutOfWorkingHours;
    case CalculationError;
}

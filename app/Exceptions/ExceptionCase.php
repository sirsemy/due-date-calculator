<?php

namespace App\Exceptions;

enum ExceptionCase
{
    case WeekendReport;
    case OutOfWorkingHours;
    case CalculationError;
}

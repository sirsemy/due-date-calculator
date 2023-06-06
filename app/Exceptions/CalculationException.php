<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response as Response;

class CalculationException extends Exception
{
    public function __construct(ExceptionCases $errorCase)
    {
        parent::__construct();

        match ($errorCase) {
            ExceptionCases::CalculationError => $this->set400ErrorMessage(),
            ExceptionCases::WeekendReport => $this->set405ErrorMessage("Report not allowed during weekend."),
            ExceptionCases::OutOfWorkingHours => $this->set405ErrorMessage("Report not allowed out of working hours."),
        };
    }

    private function set405ErrorMessage(string $errorMessage): void
    {
        $this->message = $errorMessage;
        $this->code = Response::HTTP_METHOD_NOT_ALLOWED;
    }

    private function set400ErrorMessage(): void
    {
        $this->message = 'Calculation error occurred';
        $this->code = Response::HTTP_BAD_REQUEST;
    }
}

<?php

namespace App\Exceptions;

use App\Http\Controllers\DateCalculateController;
use Exception;
use Symfony\Component\HttpFoundation\Response as Response;

class CalculationException extends Exception
{
    public function __construct(private ExceptionCase $errorCase,
                                DateCalculateController $dc = new DateCalculateController())
    {
        parent::__construct();

        match ($errorCase) {
            ExceptionCase::CalculationError => $this->set400ErrorMessage(),
            ExceptionCase::WeekendReport => $this->set405ErrorMessage("Report not allowed during weekend."),
            ExceptionCase::OutOfWorkingHours => $this->set405ErrorMessage("Report not allowed out of working hours."),
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

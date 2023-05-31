<?php

namespace App\Exceptions;

use App\Http\Controllers\DateCalculateController;
use Exception;
use Symfony\Component\HttpFoundation\Response as Response;

class CalculationException extends Exception
{
    /**
     * @param string $message
     * @param DateCalculateController $dc
     * @return void
     */
    public static function compose405ErrorMessage(string $message, DateCalculateController $dc): void
    {
        $response = response()->json(['error' =>
                ['request_time' => $message]
            ],
            Response::HTTP_METHOD_NOT_ALLOWED
        );

        $dc->setRouteResponse($response);
    }

    /**
     * @param DateCalculateController $dc
     * @return void
     */
    public static function compose400ErrorMessage(DateCalculateController $dc): void
    {
        $response = response()->json(['error' =>
                ['core_error' => 'Calculation error occurred']
            ], Response::HTTP_BAD_REQUEST
        );

        $dc->setRouteResponse($response);
    }

    /**
     * @param DateCalculateController $dc
     * @return void
     */
    public static function composeParamValidationErrorMessage(DateCalculateController $dc): void
    {
        $response = response()->json(['error' => $dc->getValidator()->errors()->getMessages()]
                , Response::HTTP_UNPROCESSABLE_ENTITY);

        $dc->setRouteResponse($response);
    }
}

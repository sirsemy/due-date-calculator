<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as Response;

class CalculationException extends Exception
{
    /**
     * @param string $message
     * @return JsonResponse
     */
    public static function return405JsonError(string $message): JsonResponse
    {
        return response()->json(['error' =>
                ['request_time' => $message]
            ],
            Response::HTTP_METHOD_NOT_ALLOWED
        );
    }

    /**
     * @param string $message
     * @return JsonResponse
     */
    public static function return400JsonError(string $message): JsonResponse
    {
        return response()->json(['error' =>
                ['core_error' => 'Calculation error occurred']
            ], Response::HTTP_BAD_REQUEST
        );
    }
}

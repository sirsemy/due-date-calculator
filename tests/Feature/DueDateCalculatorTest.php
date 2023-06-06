<?php

namespace Tests\Feature;

use App\Http\Controllers\DateCalculateController;
use DateTime;
use DateTimeInterface;
use Tests\TestCase;

class DueDateCalculatorTest extends TestCase
{
    /**
     * @return void
     *@see DateCalculateController::CalculateTaskFinishDateTime()
     *
     * GET /due_date
     *
     */
    public function test_calculator_returns_a_successful_response(): void
    {
        $this->call(
            'GET',
            '/api/due_date',
            [
                'submit_time' => '2022-06-29 09:10:00',
                'estimated_time' => 12,
            ]
        )->assertStatus(200);
    }

    /**
     * @return void
     * @see DateCalculateController::CalculateTaskFinishDateTime()
     *
     * GET /due_date
     */
    public function test_calculator_returns_with_proper_value(): void
    {
        $successValues = [
            'multiDays' => [
                'submit_time' => '2022-06-23 13:10:00',
                'estimated_time' => 20,
                'result_date' => '2022-06-28 09:10:00',
            ],
            'sameDay' => [
                'submit_time' => '2022-06-23 09:10:00',
                'estimated_time' => 5,
                'result_date' => '2022-06-23 14:10:00',
            ],
            'lessThanEightHour' => [
                'submit_time' => '2022-06-23 13:10:00',
                'estimated_time' => 7,
                'result_date' => '2022-06-24 12:10:00',
            ],
            'nextDayWeekend' => [
                'submit_time' => '2022-06-24 09:10:00',
                'estimated_time' => 12,
                'result_date' => '2022-06-27 13:10:00',
            ],
        ];

        foreach ($successValues as $value) {
            $referenceDateTime = (new DateTime())::createFromFormat('Y-m-d H:i:s', $value['result_date']);
            $compare = $referenceDateTime->format(DateTimeInterface::ATOM);

            $this->call(
                'GET',
                '/api/due_date',
                [
                    'submit_time' => $value['submit_time'],
                    'estimated_time' => $value['estimated_time'],
                ]
            )->assertJson([
                "data" => [
                    "due_date" => $compare
                ]
            ]);

            unset($referenceDateTime);
        }
    }

    /**
     * @return void
     * @see DateCalculateController::CalculateTaskFinishDateTime()
     *
     * GET /due_date
     */
    public function test_request_out_of_allowed_time(): void
    {
        $failureValues = [
            'weekendReport' => [
                'submit_time' => '2022-06-25 13:10:00',
                'estimated_time' => 20,
                'message' => 'Report not allowed during weekend.',
            ],
            'outOfWorkingHours' => [
                'submit_time' => '2022-06-23 19:10:00',
                'estimated_time' => 5,
                'message' => 'Report not allowed out of working hours.',
            ],
        ];

        foreach ($failureValues as $value) {
            $this->call(
                'GET',
                '/api/due_date',
                [
                    'submit_time' => $value['submit_time'],
                    'estimated_time' => $value['estimated_time'],
                ]
            )->assertJson([
                "error" => $value['message']
            ]);
        }
    }

    /**
     * @return void
     * @see DateCalculateController::CalculateTaskFinishDateTime()
     *
     * GET /due_date
     */
    public function test_calculator_gets_wrong_date(): void
    {
        $assertionDates = [
            'required' => "",
            'withLetters' => "adff 214d-45",
            'wrongFormat' => "2022.12.12 10:10:10",
            'wrongDateFormat' => "2022-12-12 10:10",
        ];

        $errorMessages = [
            'required' => "The submit time field is required.",
            'withLetters' => "The submit time is not a valid date.",
            'wrongFormat' => "The submit time is not a valid date.",
            'wrongDateFormat' => 'The submit time does not match the format Y-m-d H:i:s.',
        ];

        foreach ($assertionDates as $key => $value) {
            $this->call(
                'GET',
                '/api/due_date',
                [
                    "submit_time" => $value,
                    "estimated_time" => 11
                ]
            )->assertJson([
                "error" => [
                    "submit_time" => [
                        $errorMessages[$key],
                    ]
                ]
            ]);
        }
    }

    /**
     * @return void
     * @see DateCalculateController::CalculateTaskFinishDateTime()
     *
     * GET /due_date
     */
    public function test_calculator_gets_wrong_estimated_time()
    {
        $assertionDates = [
            'required' => "",
            'withLetters' => "adff",
            'lessThanMin' => -1,
        ];

        $errorMessages = [
            'required' => "The estimated time field is required.",
            'withLetters' => "The estimated time must be an integer.",
            'lessThanMin' => "The estimated time must be at least 1.",
        ];

        foreach ($assertionDates as $key => $value) {
            $this->call(
                'GET',
                '/api/due_date',
                [
                    "submit_time" => "2022-12-12 10:10:10",
                    "estimated_time" => $value
                ]
            )->assertJson([
                "error" => [
                    "estimated_time" => [
                        $errorMessages[$key],
                    ]
                ]
            ]);
        }
    }
}

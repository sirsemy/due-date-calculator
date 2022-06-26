<?php

namespace Tests\Feature;

use App\Http\Controllers\DateCalculateController;
use DateTime;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DueDateCalculatorTest extends TestCase
{
    /**
     * @see DateCalculateController::CalculateDueDate()
     *
     * GET /due_date
     *
     * @return void
     */
    public function test_calculator_returns_a_successful_response(): void
    {
        $this->call(
            'GET',
            '/api/due_date',
            [
                'submit_time' => '2022-06-29 09:10:00',
                'turnaround_time' => 12,
            ]
        )->assertStatus(200);
    }

    /**
     * @return void
     * @see DateCalculateController::CalculateDueDate()
     *
     * GET /due_date
     */
    public function test_calculator_returns_with_proper_value(): void
    {
        $successValues = [
            'multiDays' => [
                'submit_time' => '2022-06-23 13:10:00',
                'turnaround_time' => 20,
                'result_date' => '2022-06-28 09:10:00',
            ],
            'sameDay' => [
                'submit_time' => '2022-06-23 09:10:00',
                'turnaround_time' => 5,
                'result_date' => '2022-06-23 14:10:00',
            ],
            'lessThanEightHour' => [
                'submit_time' => '2022-06-23 13:10:00',
                'turnaround_time' => 7,
                'result_date' => '2022-06-24 12:10:00',
            ],
            'nextDayWeekend' => [
                'submit_time' => '2022-06-24 09:10:00',
                'turnaround_time' => 12,
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
                    'turnaround_time' => $value['turnaround_time'],
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
     * @see DateCalculateController::CalculateDueDate()
     *
     * GET /due_date
     */
    public function test_calculator_returns_with_error_response(): void
    {
        $failureValues = [
            'weekendReport' => [
                'submit_time' => '2022-06-25 13:10:00',
                'turnaround_time' => 20,
                'message' => 'Report not allowed during weekend.',
            ],
            'outOfWorkingHours' => [
                'submit_time' => '2022-06-23 19:10:00',
                'turnaround_time' => 5,
                'message' => 'Report not allowed out of working hours.',
            ],
        ];

        foreach ($failureValues as $value) {
            $this->call(
                'GET',
                '/api/due_date',
                [
                    'submit_time' => $value['submit_time'],
                    'turnaround_time' => $value['turnaround_time'],
                ]
            )->assertJson([
                "error" => [
                    "request_time" => $value['message']
                ]
            ]);
        }
    }

    /**
     * @return void
     * @see DateCalculateController::CalculateDueDate()
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
                    "turnaround_time" => 11
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
     * @see DateCalculateController::CalculateDueDate()
     *
     * GET /due_date
     */
    public function test_calculator_gets_wrong_turnaround_time()
    {
        $assertionDates = [
            'required' => "",
            'withLetters' => "adff",
            'lessThanMin' => -1,
        ];

        $errorMessages = [
            'required' => "The turnaround time field is required.",
            'withLetters' => "The turnaround time must be an integer.",
            'lessThanMin' => "The turnaround time must be at least 1.",
        ];

        foreach ($assertionDates as $key => $value) {
            $this->call(
                'GET',
                '/api/due_date',
                [
                    "submit_time" => "2022-12-12 10:10:10",
                    "turnaround_time" => $value
                ]
            )->assertJson([
                "error" => [
                    "turnaround_time" => [
                        $errorMessages[$key],
                    ]
                ]
            ]);
        }
    }
}

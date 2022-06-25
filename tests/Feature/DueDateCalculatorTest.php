<?php

namespace Tests\Feature;

use App\Http\Controllers\DateCalculateController;
use DateTime;
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
    public function test_calculator_returns_a_successful_response()
    {
        $response = $this->get('/api/due_date',
            [
                'submit_time' => '2022-06-29 09:10:00',
                'turnaround_time' => 12,
            ]);

        $response->assertStatus(200);
    }

    /**
     * @return void
     * @see DateCalculateController::CalculateDueDate()
     *
     * GET /due_date
     */
    public function test_calculator_returns_with_proper_value()
    {
        $response = $this->get('/due_date',
            [
                'submit_time' => '2022-06-23 13:10:00',
                'turnaround_time' => 20,
            ]);

        $this->assertJsonStringEqualsJsonString(json_encode('[{\"due_date\": \"2022-06-29T09:10:00+01:00\"}]'),
            $response->getContent());

    }

    /**
     * @return void
     * @see DateCalculateController::CalculateDueDate()
     *
     * GET /due_date
     */
    public function test_resolved_date_on_same_day_success()
    {
        $response = $this->get('/due_date',
            [
                'submit_time' => '2022-06-23 09:10:00',
                'turnaround_time' => 5,
            ]);

        $this->assertJsonStringEqualsJsonString(json_encode('[{\"due_date\": \"2022-06-23T14:10:00+01:00\"}]'),
            $response->getContent());
    }

    /**
     * @return void
     * @see DateCalculateController::CalculateDueDate()
     *
     * GET /due_date
     */
    public function test_next_day_is_weekend_success()
    {
        $response = $this->get('/due_date',
            [
                'submit_time' => '2022-06-24 09:10:00',
                'turnaround_time' => 12,
            ]);

        $this->assertJsonStringEqualsJsonString(json_encode('[{\"due_date\": \"2022-06-23T14:10:00+01:00\"}]'),
            $response->getContent());
    }

    /**
     * @return void
     * @see DateCalculateController::CalculateDueDate()
     *
     * GET /due_date
     */
    public function test_calculator_gets_wrong_date()
    {
        $response = $this->get('/due_date',
            [
                'submit_time' => 'adfaf 25-112-a',
                'turnaround_time' => 12,
            ]);

    }

    /**
     * @return void
     * @see DateCalculateController::CalculateDueDate()
     *
     * GET /due_date
     */
    public function test_calculator_gets_wrong_turnaround_time()
    {
        $response = $this->get('/due_date',
            [
                'submit_time' => '2022-06-29 09:10:00',
                'turnaround_time' => 'ab',
            ]);

    }
}

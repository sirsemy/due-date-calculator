<?php

namespace Tests\Feature;

use App\Http\Controllers\DateCalculateController;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $response = $this->get('/due_date');

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

    }

    /**
     * @return void
     * @see DateCalculateController::CalculateDueDate()
     *
     * GET /due_date
     */
    public function test_success_with_resolved_date_on_current_day()
    {

    }

    /**
     * @return void
     * @see DateCalculateController::CalculateDueDate()
     *
     * GET /due_date
     */
    public function test_success_with_next_day_is_weekend()
    {

    }

    /**
     * @return void
     * @see DateCalculateController::CalculateDueDate()
     *
     * GET /due_date
     */
    public function test_calculator_gets_wrong_date()
    {

    }

    /**
     * @return void
     * @see DateCalculateController::CalculateDueDate()
     *
     * GET /due_date
     */
    public function test_calculator_gets_wrong_turnaround_time()
    {

    }
}

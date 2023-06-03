<?php

namespace Tests\Unit;

use App\Http\Controllers\DateCalculateController;
use DateTime;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class DateCalculateControllerTest extends TestCase
{
    /**
     * @var DateCalculateController
     */
    protected static DateCalculateController $calculateController;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        self::$calculateController = new DateCalculateController();
    }

    /**
     * @return void
     * @see DateCalculateController::isNextDayIsWeekendDay()
     */
    public function testNextDayIsWeekendDay(): void
    {
        $dateValue = $this->dateTime->setDate(2022, 06, 24)->setTime(13, 10);
        $functionResponse = '';

        try {
            $functionResponse = self::$calculateController->isNextDayIsWeekendDay($dateValue);
        } catch (\Exception $e) {
            Log::error('DateInterval not worked during calculate multiple working days. Error message: ' .
                $e->getMessage());
        }

        $this->assertTrue($functionResponse);
    }

    /**
     * @return void
     * @see DateCalculateController::isNextDayIsWeekendDay()
     */
    public function testNextDayIsWorkingDay(): void
    {
        $dateValue = $this->dateTime->setDate(2022, 06, 22)->setTime(13, 10);
        $functionResponse = '';

        try {
            $functionResponse = self::$calculateController->isNextDayIsWeekendDay($dateValue);
        } catch (\Exception $e) {
            Log::error('DateInterval not worked during calculate multiple working days. Error message: ' .
                $e->getMessage());
        }

        $this->assertFalse($functionResponse);
    }
}

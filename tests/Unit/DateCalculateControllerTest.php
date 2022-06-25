<?php

namespace Tests\Unit;

use App\Http\Controllers\DateCalculateController;
use DateTime;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DateCalculateControllerTest extends TestCase
{
    /**
     * @var DateCalculateController
     */
    protected static DateCalculateController $calculateController;

    protected function setUp(): void
    {
        parent::setUp();

        self::$calculateController = new DateCalculateController();
    }

    /**
     * @return void
     * @see DateCalculateController::isProblemReportedDuringWorkingHours()
     */
    public function testProblemReportedDuringWorkingHours()
    {
        $this->dateTime->setDate(2022, 06, 24)->setTime(10, 10);
        $insertDateTime = $this->dateTime->format('Y-m-d H:i:s');

        $functionResponse = self::$calculateController->isProblemReportedDuringWorkingHours($insertDateTime);

        $this->assertTrue($functionResponse);
    }

    /**
     * @return void
     * @see DateCalculateController::isProblemReportedDuringWorkingHours()
     */
    public function testProblemReportedOutsideWorkingHours()
    {
        $this->dateTime->setDate(2022, 06, 24)->setTime(07, 10);
        $insertDateTime = $this->dateTime->format('Y-m-d H:i:s');

        $functionResponse = self::$calculateController->isProblemReportedDuringWorkingHours($insertDateTime);

        $this->assertFalse($functionResponse);
    }

    /**
     * @return void
     * @see DateCalculateController::isProblemReportedOnWorkingDays()
     */
    public function testProblemReportedOnWorkDays()
    {
        $this->dateTime->setDate(2022, 06, 24)->setTime(07, 10);
        $insertDateTime = $this->dateTime->format('Y-m-d H:i:s');

        $functionResponse = self::$calculateController->isProblemReportedOnWorkingDays($insertDateTime);

        $this->assertTrue($functionResponse);
    }

    /**
     * @return void
     * @see DateCalculateController::isProblemReportedOnWorkingDays()
     */
    public function testProblemReportedOnWeekendDays()
    {
        $this->dateTime->setDate(2022, 06, 25)->setTime(13, 10);
        $insertDateTime = $this->dateTime->format('Y-m-d H:i:s');

        $functionResponse = self::$calculateController->isProblemReportedOnWorkingDays($insertDateTime);

        $this->assertFalse($functionResponse);
    }

    /**
     * @return void
     * @see DateCalculateController::canProblemSolvableSameDay()
     */
    public function testProblemCanSolveSameDay()
    {
        $this->dateTime->setDate(2022, 06, 24)->setTime(13, 10);
        $insertDateTime = $this->dateTime->format('Y-m-d H:i:s');
        $turnaroundTime = 3;

        $functionResponse = self::$calculateController->canProblemSolvableSameDay($insertDateTime, $turnaroundTime);

        $this->assertTrue($functionResponse);
    }

    /**
     * @return void
     * @see DateCalculateController::canProblemSolvableSameDay()
     */
    public function testProblemNeedsMoreTimeWhatIsAvailableToday()
    {
        $this->dateTime->setDate(2022, 06, 24)->setTime(13, 10);
        $insertDateTime = $this->dateTime->format('Y-m-d H:i:s');
        $turnaroundTime = 8;

        $functionResponse = self::$calculateController->canProblemSolvableSameDay($insertDateTime, $turnaroundTime);

        $this->assertFalse($functionResponse);
    }

    /**
     * @return void
     * @see DateCalculateController::checkProblemNeedsLessTimeThanOneWorkday()
     */
    public function testProblemNeedsLessTimeThanOneWorkday()
    {
        $turnaroundTime = 7;

        $functionResponse = self::$calculateController->checkProblemNeedsLessTimeThanOneWorkday($turnaroundTime);

        $this->assertTrue($functionResponse);
    }

    /**
     * @return void
     * @see DateCalculateController::checkProblemNeedsLessTimeThanOneWorkday()
     */
    public function testProblemNeedsMoreThanOneWorkday()
    {
        $turnaroundTime = 12;

        $functionResponse = self::$calculateController->checkProblemNeedsLessTimeThanOneWorkday($turnaroundTime);

        $this->assertFalse($functionResponse);
    }

    /**
     * @return void
     * @see DateCalculateController::checkNextDayIsWeekendDay()
     */
    public function testNextDayIsWeekendDay()
    {
        $functionResponse = self::$calculateController->checkNextDayIsWeekendDay();

        $this->assertTrue($functionResponse);
    }

    /**
     * @return void
     * @see DateCalculateController::checkNextDayIsWeekendDay()
     */
    public function testNextDayIsWorkingDay()
    {
        $functionResponse = self::$calculateController->checkNextDayIsWeekendDay();

        $this->assertFalse($functionResponse);
    }

    /**
     * @return void
     * @see DateCalculateController::calculateMultipleWorkingDays()
     */
    public function testCalculateMultipleWorkingDaysSuccess()
    {
        $this->dateTime->setDate(2022, 06, 23)->setTime(13, 10);
        $insertDateTime = $this->dateTime->format('Y-m-d H:i:s');
        $turnaroundTime = 20;

        $functionResponse = self::$calculateController->calculateMultipleWorkingDays($insertDateTime, $turnaroundTime);

        $this->assertEquals('2022-06-29T09:10:00+01:00', $functionResponse);
    }
}

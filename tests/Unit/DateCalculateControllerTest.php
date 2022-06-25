<?php

namespace Tests\Unit;

use App\Http\Controllers\DateCalculateController;
use PHPUnit\Framework\TestCase;

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
     * @see DateCalculateController::isProblemReportedDuringWorkingHours()
     */
    public function testProblemReportedDuringWorkingHours()
    {

    }

    /**
     * @return void
     * @see DateCalculateController::isProblemReportedDuringWorkingHours()
     */
    public function testProblemReportedOutsideWorkingHours()
    {

    }

    /**
     * @return void
     * @see DateCalculateController::isProblemReportedOnWorkingDays()
     */
    public function testProblemReportedOnWorkDays()
    {

    }

    /**
     * @return void
     * @see DateCalculateController::isProblemReportedOnWorkingDays()
     */
    public function testProblemReportedOnWeekendDays()
    {

    }

    /**
     * @return void
     * @see DateCalculateController::canProblemSolvableToday()
     */
    public function testProblemCanSolveToday()
    {

    }

    /**
     * @return void
     * @see DateCalculateController::canProblemSolvableToday()
     */
    public function testProblemNeedsMoreTimeWhatIsAvailableToday()
    {

    }

    /**
     * @return void
     * @see DateCalculateController::checkProblemNeedsLessTimeThanOneWorkday()
     */
    public function testProblemNeedsLessTimeThanOneWorkday()
    {

    }

    /**
     * @return void
     * @see DateCalculateController::checkProblemNeedsLessTimeThanOneWorkday()
     */
    public function testProblemNeedsMoreThanOneWorkday()
    {

    }

    /**
     * @return void
     * @see DateCalculateController::checkNextDayIsWeekendDay()
     */
    public function testNextDayIsWeekendDay()
    {

    }

    /**
     * @return void
     * @see DateCalculateController::checkNextDayIsWeekendDay()
     */
    public function testNextDayIsWorkingDay()
    {

    }

    /**
     * @return void
     * @see DateCalculateController::calculateMultipleWorkingDays()
     */
    public function testCalculateMultipleWorkingDaysSuccess()
    {
        $this->assertTrue(true);
    }
}

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
     * @see DateCalculateController::isProblemReportedDuringWorkingHours()
     */
    public function testProblemReportedDuringWorkingHours(): void
    {
        $dateValue = $this->dateTime->setDate(2022, 06, 24)->setTime(10, 10);

        $functionResponse = self::$calculateController->isProblemReportedDuringWorkingHours($dateValue);

        $this->assertTrue($functionResponse);
    }

    /**
     * @return void
     * @see DateCalculateController::isProblemReportedDuringWorkingHours()
     */
    public function testProblemReportedOutsideWorkingHours(): void
    {
        $dateValue = $this->dateTime->setDate(2022, 06, 24)->setTime(07, 10);

        $functionResponse = self::$calculateController->isProblemReportedDuringWorkingHours($dateValue);

        $this->assertFalse($functionResponse);
    }

    /**
     * @return void
     * @see DateCalculateController::isProblemReportedDuringWorkingHours()
     */
    public function testProblemReportedCoupleOfMinutesAfterWorkingHours(): void
    {
        $dateValue = $this->dateTime->setDate(2022, 06, 24)->setTime(17, 10);

        $functionResponse = self::$calculateController->isProblemReportedDuringWorkingHours($dateValue);

        $this->assertFalse($functionResponse);
    }

    /**
     * @return void
     * @see DateCalculateController::isProblemReportedOnWorkingDays()
     */
    public function testProblemReportedOnWorkDays(): void
    {
        $dateValue = $this->dateTime->setDate(2022, 06, 24)->setTime(07, 10);

        $functionResponse = self::$calculateController->isProblemReportedOnWorkingDays($dateValue);

        $this->assertTrue($functionResponse);
    }

    /**
     * @return void
     * @see DateCalculateController::isProblemReportedOnWorkingDays()
     */
    public function testProblemReportedOnWeekendDays(): void
    {
        $dateValue = $this->dateTime->setDate(2022, 06, 25)->setTime(13, 10);

        $functionResponse = self::$calculateController->isProblemReportedOnWorkingDays($dateValue);

        $this->assertFalse($functionResponse);
    }

    /**
     * @return void
     * @see DateCalculateController::canProblemSolvableSameDay()
     */
    public function testProblemCanSolveSameDay(): void
    {
        $dateValue = $this->dateTime->setDate(2022, 06, 24)->setTime(13, 10);
        $turnaroundTime = 3;

        $functionResponse = self::$calculateController->canProblemSolvableSameDay($dateValue, $turnaroundTime);

        $this->assertTrue($functionResponse);
    }

    /**
     * @return void
     * @see DateCalculateController::canProblemSolvableSameDay()
     */
    public function testProblemNeedsMoreTimeWhatIsAvailableToday(): void
    {
        $dateValue = $this->dateTime->setDate(2022, 06, 24)->setTime(13, 10);
        $turnaroundTime = 8;

        $functionResponse = self::$calculateController->canProblemSolvableSameDay($this->dateTime, $turnaroundTime);

        $this->assertFalse($functionResponse);
    }

    /**
     * @return void
     * @see DateCalculateController::canProblemSolvableSameDay()
     */
    public function testProblemNeedsCoupleOfMinutesMoreTimeWhatIsAvailableToday(): void
    {
        $dateValue = $this->dateTime->setDate(2022, 06, 24)->setTime(13, 10);
        $turnaroundTime = 4;

        $functionResponse = self::$calculateController->canProblemSolvableSameDay($this->dateTime, $turnaroundTime);

        $this->assertFalse($functionResponse);
    }

    /**
     * @return void
     * @see DateCalculateController::isProblemNeedsLessTimeThanOneWorkday()
     */
    public function testProblemNeedsLessTimeThanOneWorkday(): void
    {
        $turnaroundTime = 7;

        $functionResponse = self::$calculateController->isProblemNeedsLessTimeThanOneWorkday($turnaroundTime);

        $this->assertTrue($functionResponse);
    }

    /**
     * @return void
     * @see DateCalculateController::isProblemNeedsLessTimeThanOneWorkday()
     */
    public function testProblemNeedsMoreThanOneWorkday(): void
    {
        $turnaroundTime = 12;

        $functionResponse = self::$calculateController->isProblemNeedsLessTimeThanOneWorkday($turnaroundTime);

        $this->assertFalse($functionResponse);
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

    /**
     * @return void
     *
     * @see DateCalculateController::calculateMultipleWorkingDays()
     */
    public function testCalculateMultipleWorkingDaysSuccess(): void
    {
        $dateValue = $this->dateTime->setDate(2022, 06, 23)->setTime(13, 10);
        $referenceDateTime = (new DateTime())::createFromFormat('Y-m-d H:i:s', '2022-06-28 9:10:00');
        $turnaroundTime = 20;
        $isoDateTime = '';

        try {
            $functionResponse = self::$calculateController->calculateMultipleWorkingDays($dateValue, $turnaroundTime);
            $isoDateTime = $functionResponse->format(DateTimeInterface::ATOM);
        } catch (\Exception $e) {
            Log::error('DateInterval not worked during calculate multiple working days. Error message: ' .
                $e->getMessage());
        }

        $compare = $referenceDateTime->format(DateTimeInterface::ATOM);

        $this->assertEquals($compare, $isoDateTime);
    }

    /**
     * @return void
     *
     * @see DateCalculateController::calculateMultipleWorkingDays()
     */
    public function testCalculateMultipleWorkingDaysWithEndSameDaySolving(): void
    {
        $dateValue = $this->dateTime->setDate(2022, 06, 23)->setTime(13, 10);
        $referenceDateTime = (new DateTime())::createFromFormat('Y-m-d H:i:s', '2022-06-27 16:10:00');
        $turnaroundTime = 19;
        $isoDateTime = '';

        try {
            $functionResponse = self::$calculateController->calculateMultipleWorkingDays($dateValue, $turnaroundTime);
            $isoDateTime = $functionResponse->format(DateTimeInterface::ATOM);
        } catch (\Exception $e) {
            Log::error('DateInterval not worked during calculate multiple working days. Error message: ' .
                $e->getMessage());
        }

        $compare = $referenceDateTime->format(DateTimeInterface::ATOM);

        $this->assertEquals($compare, $isoDateTime);
    }

    /**
     * @return void
     *
     * @see DateCalculateController::calculateMultipleWorkingDays()
     */
    public function testCalculateMultipleWorkingDaysEndsAfterNineAM(): void
    {
        $dateValue = $this->dateTime->setDate(2022, 06, 23)->setTime(13, 10);
        $referenceDateTime = (new DateTime())::createFromFormat('Y-m-d H:i:s', '2022-06-28 10:10:00');
        $turnaroundTime = 21;
        $isoDateTime = '';

        try {
            $functionResponse = self::$calculateController->calculateMultipleWorkingDays($dateValue, $turnaroundTime);
            $isoDateTime = $functionResponse->format(DateTimeInterface::ATOM);
        } catch (\Exception $e) {
            Log::error('DateInterval not worked during calculate multiple working days. Error message: ' .
                $e->getMessage());
        }

        $compare = $referenceDateTime->format(DateTimeInterface::ATOM);

        $this->assertEquals($compare, $isoDateTime);
    }

    /**
     * @return void
     *
     * @see DateCalculateController::calculateMultipleWorkingDays()
     */
    public function testCalculateMultipleWorkingDaysRemainHours(): void
    {
        $dateValue = $this->dateTime->setDate(2022, 06, 22)->setTime(13, 10);
        $referenceDateTime = (new DateTime())::createFromFormat('Y-m-d H:i:s', '2022-06-27 11:10:00');
        $turnaroundTime = 22;
        $isoDateTime = '';

        try {
            $functionResponse = self::$calculateController->calculateMultipleWorkingDays($dateValue, $turnaroundTime);
            $isoDateTime = $functionResponse->format(DateTimeInterface::ATOM);
        } catch (\Exception $e) {
            Log::error('DateInterval not worked during calculate multiple working days. Error message: ' .
                $e->getMessage());
        }

        $compare = $referenceDateTime->format(DateTimeInterface::ATOM);

        $this->assertEquals($compare, $isoDateTime);
    }
}

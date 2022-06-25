<?php

namespace App\Http\Controllers;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DateCalculateController extends Controller
{
    /**
     * Number value when starting work on working days in the morning.
     *
     * @var int
     */
    private const STARTING_WORK_HOUR = 9;

    /**
     * Number value when finishing work on working days in the afternoon.
     *
     * @var int
     */
    private const FINISHING_WORK_HOUR = 17;

    /**
     * Calculate due date from submitted date and turnaround time.
     *
     * @Route("/due_date", methods={"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function CalculateDueDate(Request $request): JsonResponse
    {

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'submit_time' => 'required|date',
            'turnaround_time' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            $errorMessages = $validator->errors()->getMessages();

            return \response('', 422)->json(['error' => $errorMessages]);
        }

        $this->isProblemReportedOnWorkingDays();
        $this->isProblemReportedDuringWorkingHours();
        $this->canProblemSolvableSameDay();
        $this->isProblemNeedsLessTimeThanOneWorkday();

        try {
            $calculatedDate = $this->calculateMultipleWorkingDays();
        } catch (\Exception $e) {
            Log::error('DateInterval not worked during calculate multiple working days. Error message: ' .
                $e->getMessage());
            return \response()->json(['error' => 'Calculation error occurred']);
        }

        return \response()->json(['data' => [
            'due_date' => $calculatedDate->format(DateTimeInterface::ATOM),
        ]], 200);
    }

    /**
     * @param DateTimeImmutable $submittedDateTime
     *
     * @return bool
     */
    public function isProblemReportedOnWorkingDays(DateTimeImmutable $submittedDateTime): bool
    {
        $examineDay = (int)$submittedDateTime->format('N');

        if ($examineDay >= 6) {
            return false;
        }

        return true;
    }

    /**
     * @param DateTimeImmutable $submittedDateTime
     *
     * @return bool
     */
    public function isProblemReportedDuringWorkingHours(DateTimeImmutable $submittedDateTime): bool
    {
        $examineHour = (int)$submittedDateTime->format('H');
        $examineMinute = (int)$submittedDateTime->format('i');

        if ($examineHour >= self::STARTING_WORK_HOUR &&
            $examineHour <= self::FINISHING_WORK_HOUR
        ) {
            if ($examineHour === self::FINISHING_WORK_HOUR && $examineMinute !== 0) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Check whether are there enough time to solve the issue today.
     *
     * @param DateTimeImmutable $submittedDateTime
     * @param int $turnaroundTime
     *
     * @return bool
     */
    public function canProblemSolvableSameDay(DateTimeImmutable $submittedDateTime, int $turnaroundTime): bool
    {
        $examineHour = (int)$submittedDateTime->format('H');
        $examineMinute = (int)$submittedDateTime->format('i');
        $summaTime = $examineHour + $turnaroundTime;

        if ($summaTime <= self::FINISHING_WORK_HOUR) {
            if ($summaTime === self::FINISHING_WORK_HOUR && $examineMinute !== 0) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * @param int $turnaroundTime
     *
     * @return bool
     */
    public function isProblemNeedsLessTimeThanOneWorkday(int $turnaroundTime): bool
    {
        return $turnaroundTime < 8;
    }

    /**
     * @param DateTimeImmutable $submittedDateTime
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function isNextDayIsWeekendDay(DateTimeImmutable $submittedDateTime): bool
    {
        $nextDay = $submittedDateTime->add(new \DateInterval('P1D'));

        if ($nextDay->format('N') >= 6) {
            return true;
        }

        return false;
    }

    /**
     * @param DateTimeImmutable $submittedDateTime
     * @param int $turnaroundTime
     *
     * @return DateTime
     *
     * @throws \Exception
     */
    public function calculateMultipleWorkingDays(DateTimeImmutable $submittedDateTime, int $turnaroundTime): DateTime
    {
        $calculateDate = (new DateTime())::createFromImmutable($submittedDateTime);
        $submittedHour = (int)$submittedDateTime->format('H');
        $submittedMinutes = (int)$submittedDateTime->format('i');
        $workDaysAmount = intdiv($turnaroundTime, 8);
        $remainHour = $turnaroundTime % 8;

        while ($workDaysAmount > 0) {
            $checkNextDayDateTime = (new DateTimeImmutable())::createFromMutable($calculateDate);
            if ($this->isNextDayIsWeekendDay($checkNextDayDateTime)) {
                $calculateDate->add(new \DateInterval('P3D'));
                $workDaysAmount--;
                continue;
            }

            $calculateDate->add(new \DateInterval('P1D'));
            $workDaysAmount--;
        }

        $checkWithRemainedTime = (new DateTimeImmutable())::createFromMutable($calculateDate);

        if ($remainHour && $this->canProblemSolvableSameDay($checkWithRemainedTime, $remainHour)) {
            return $calculateDate->add(new \DateInterval('PT' . $remainHour . 'H'));
        }

        $checkNextDayDateTime = (new DateTimeImmutable())::createFromMutable($calculateDate);

        if ($this->isNextDayIsWeekendDay($checkNextDayDateTime)) {
            $calculateDate->add(new \DateInterval('P3D'))->setTime(9, $submittedMinutes);
        } else {
            $calculateDate->add(new \DateInterval('P1D'))->setTime(9, $submittedMinutes);
        }

        $remainHour = ($submittedHour + $remainHour) - self::FINISHING_WORK_HOUR;

        if (empty($remainHour)) {
            return $calculateDate;
        }

        return $calculateDate->add(new \DateInterval('PT' . $remainHour . 'H'));
    }
}

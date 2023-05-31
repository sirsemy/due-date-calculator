<?php

namespace App\Http\Controllers;

use App\Exceptions\CalculationException;
use App\Exceptions\ExceptionCase;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
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

    private JsonResponse $routeResponse;
    private DateTimeImmutable|DateTime $calculatedDate;

    private string $submittedDate;
    private int $turnaroundTime;

    /**
     * Calculate due date from submitted date and turnaround time.
     *
     * @Route("/due_date", methods={"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     * @throws CalculationException
     * @throws ValidationException
     */
    public function CalculateTaskFinishDateTime(Request $request): JsonResponse
    {
        $this->validateParameters($request);

        $this->submittedDate = $request->input('submit_time');
        $this->turnaroundTime = $request->input('turnaround_time');

        $dateTime = (new DateTimeImmutable())::createFromFormat('Y-m-d H:i:s', $this->submittedDate);

        if (!$this->isProblemReportedOnWorkingDays($dateTime)) {
            throw new CalculationException(ExceptionCase::WeekendReport);
        }

        if (!$this->isProblemReportedDuringWorkingHours($dateTime)) {
            throw new CalculationException(ExceptionCase::OutOfWorkingHours);
        }

        if ($this->canProblemSolvableSameDay($dateTime, $this->turnaroundTime)) {
            try {
                $this->calculatedDate = $dateTime->add(new \DateInterval('PT' . $this->turnaroundTime . 'H'));
            } catch (\Exception $e) {
                Log::error('DateInterval not worked during calculate multiple working days. Error message: ' .
                    $e->getMessage());
                throw new CalculationException(ExceptionCase::CalculationError);
            }

            $this->composeSuccessResponse();

            return $this->routeResponse;
        }

        try {
            $this->calculatedDate = $this->calculateMultipleWorkingDays($dateTime, $this->turnaroundTime);
        } catch (\Exception $e) {
            Log::error('DateInterval not worked during calculate multiple working days. Error message: ' .
                $e->getMessage());
            throw new CalculationException(ExceptionCase::CalculationError);
        }

        $this->composeSuccessResponse();

        return $this->routeResponse;
    }

    /**
     * @throws ValidationException
     */
    private function validateParameters(Request $request): void
    {
        $validator = Validator::make($request->all(), [
            'submit_time' => 'required|date|date_format:Y-m-d H:i:s',
            'turnaround_time' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Check whether the user reported issue on weekend days.
     *
     * @param DateTimeImmutable $submittedDateTime This DateTime must be immutable because we need protect the original
     *  submit date.
     *
     * @return bool Return with true or false value.
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
     * Check whether the user reported issue out of working hours. Before or after.
     *
     * @param DateTimeImmutable $submittedDateTime This DateTime must be immutable because we need protect the original
     *  submit date.
     *
     * @return bool Return with true or false value.
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
     * @param DateTimeImmutable $submittedDateTime This DateTime must be immutable because we need protect the original
     *  submit date.
     * @param int $turnaroundTime The integer value from user. The number how many hours should need solve the issue.
     *
     * @return bool Return with true or false value.
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
     * Check whether the next day is Saturday.
     *
     * @param DateTimeImmutable $submittedDateTime This DateTime must be immutable because we need protect the original
     *  submit date.
     *
     * @return bool Return with true or false value.
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
     * @param DateTimeImmutable $submittedDateTime This DateTime must be immutable because we need protect the original
     *  submit date.
     * @param int $turnaroundTime The integer value from user. The number how many hours should need solve the issue.
     *
     * @return DateTime It must be return with DateTime object for the easy convert to ISO date format.
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

    /**
     * @return void
     */
    private function composeSuccessResponse(): void
    {
        $this->routeResponse = response()->json(['data' => [
            'due_date' => $this->calculatedDate->format(DateTimeInterface::ATOM),
        ]], \Symfony\Component\HttpFoundation\Response::HTTP_OK);
    }
}

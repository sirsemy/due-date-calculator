<?php

namespace App\Http\Controllers;

use App\Exceptions\CalculationException;
use App\Exceptions\ExceptionCase;
use App\Http\Helpers\CalculateTimeDemand;
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
     * @var int
     */
    private const STARTING_WORK_HOUR = 9;

    /**
     * @var int
     */
    private const FINISHING_WORK_HOUR = 17;

    private const DATE_TIME_FORMAT = 'Y-m-d H:i:s';
    private const WEEK_DAY_FORMAT = 'N';
    private const HOUR_MINUTE_FORMAT = 'H:i';

    private JsonResponse $routeResponse;
    private DateTimeImmutable|DateTime $calculatedDate;
    private DateTimeImmutable|DateTime $submittedDateTime;

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

        $submittedDate = $request->input('submit_time');
        $this->turnaroundTime = $request->input('turnaround_time');

        $this->submittedDateTime = (new DateTimeImmutable())::createFromFormat(self::DATE_TIME_FORMAT, $submittedDate);

        $this->checkProblemReportedOnWorkingDays($this->submittedDateTime);
        $this->checkProblemReportedDuringWorkingHours($this->submittedDateTime);

        if ($this->canProblemSolvableSameDay($this->submittedDateTime, $this->turnaroundTime)) {
            CalculateTimeDemand::calculateSameDayTime($this);

            $this->composeSuccessResponse();

            return $this->routeResponse;
        }

        CalculateTimeDemand::calculateMultipleDaysTime($this);

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
     * @param DateTimeImmutable $submittedDateTime This DateTime must be immutable because we need protect the original
     *  submit date.
     *
     * @throws CalculationException
     */
    private function checkProblemReportedOnWorkingDays(DateTimeImmutable $submittedDateTime): void
    {
        $examineDay = (int)$submittedDateTime->format(self::WEEK_DAY_FORMAT);

        if ($examineDay >= 6) {
            throw new CalculationException(ExceptionCase::WeekendReport);
        }
    }

    /**
     * @param DateTimeImmutable $submittedDateTime This DateTime must be immutable because we need protect the original
     *  submit date.
     *
     * @throws CalculationException
     */
    private function checkProblemReportedDuringWorkingHours(DateTimeImmutable $submittedDateTime): void
    {
        $submittedTime = $submittedDateTime->format(self::HOUR_MINUTE_FORMAT);

        $startTime = (new DateTime())->setTime(self::STARTING_WORK_HOUR, 0)
            ->format(self::HOUR_MINUTE_FORMAT);
        $finishTime = (new DateTime())->setTime(self::FINISHING_WORK_HOUR, 0)
            ->format(self::HOUR_MINUTE_FORMAT);

        if ($submittedTime < $startTime || $submittedTime > $finishTime) {
            throw new CalculationException(ExceptionCase::OutOfWorkingHours);
        }
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

        if ($nextDay->format(self::WEEK_DAY_FORMAT) >= 6) {
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

    /**
     * @param DateTime|DateTimeImmutable $calculatedDate
     */
    public function setCalculatedDate(DateTimeImmutable|DateTime $calculatedDate): void
    {
        $this->calculatedDate = $calculatedDate;
    }

    /**
     * @return int
     */
    public function getTurnaroundTime(): int
    {
        return $this->turnaroundTime;
    }

    /**
     * @return DateTime|DateTimeImmutable
     */
    public function getSubmittedDateTime(): DateTimeImmutable|DateTime
    {
        return $this->submittedDateTime;
    }
}

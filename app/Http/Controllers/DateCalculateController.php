<?php

namespace App\Http\Controllers;

use App\Exceptions\CalculationException;
use App\Exceptions\ExceptionCase;
use App\Http\Helpers\CalculateTimeDemand;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Routing\Annotation\Route;

class DateCalculateController extends Controller
{
    private const STARTING_WORK_HOUR = 9;
    private const FINISHING_WORK_HOUR = 17;
    private const WORKING_HOURS_PER_DAY = 8;

    private const DATE_TIME_FORMAT = 'Y-m-d H:i:s';
    private const WEEK_DAY_FORMAT = 'N';
    private const HOUR_MINUTE_FORMAT = 'H:i';

    private JsonResponse $routeResponse;
    private DateTimeImmutable|DateTime $calculatedDate;
    private DateTimeImmutable $submittedDateTime;

    private int $estimatedTime;

    /**
     * Calculate due date from submitted date and estimated time.
     *
     * @Route("/due_date", methods={"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     * @throws CalculationException
     * @throws ValidationException
     * @throws Exception
     */
    public function CalculateTaskFinishDateTime(Request $request): JsonResponse
    {
        $this->validateParameters($request);

        $submittedDate = $request->input('submit_time');
        $this->estimatedTime = $request->input('estimated_time');

        $this->submittedDateTime = (new DateTimeImmutable())::createFromFormat(self::DATE_TIME_FORMAT, $submittedDate);

        $this->checkProblemReportedOnWorkingDays();
        $this->checkProblemReportedDuringWorkingHours();

        $this->runCalculation();

        return $this->routeResponse;
    }

    /**
     * @throws ValidationException
     */
    private function validateParameters(Request $request): void
    {
        $validator = Validator::make($request->all(), [
            'submit_time' => 'required|date|date_format:Y-m-d H:i:s',
            'estimated_time' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * @throws CalculationException
     */
    private function checkProblemReportedOnWorkingDays(): void
    {
        $examineDay = (int)$this->submittedDateTime->format(self::WEEK_DAY_FORMAT);

        if ($examineDay >= 6) {
            throw new CalculationException(ExceptionCase::WeekendReport);
        }
    }

    /**
     * @throws CalculationException
     */
    private function checkProblemReportedDuringWorkingHours(): void
    {
        $submittedTime = $this->submittedDateTime->format(self::HOUR_MINUTE_FORMAT);

        $startTime = (new DateTime())->setTime(self::STARTING_WORK_HOUR, 0)
            ->format(self::HOUR_MINUTE_FORMAT);
        $finishTime = (new DateTime())->setTime(self::FINISHING_WORK_HOUR, 0)
            ->format(self::HOUR_MINUTE_FORMAT);

        if ($submittedTime < $startTime || $submittedTime > $finishTime) {
            throw new CalculationException(ExceptionCase::OutOfWorkingHours);
        }
    }

    /**
     * @throws CalculationException
     * @throws Exception
     */
    private function runCalculation(): void
    {
        if ($this->canProblemSolvableSameDay()) {
            CalculateTimeDemand::calculateSameDayTime($this);
        } else {
            CalculateTimeDemand::calculateMultipleDaysTime($this);
        }

        $this->composeSuccessResponse();
    }

    /**
     * @throws Exception
     */
    public function canProblemSolvableSameDay(): bool
    {
        $summaDate = $this->submittedDateTime->add(new \DateInterval('PT' . $this->estimatedTime . 'H'));
        $finishTime = $this->submittedDateTime->setTime(self::FINISHING_WORK_HOUR, 0);

        if ($summaDate > $finishTime) {
            return false;
        }

        return true;
    }

    /**
     * @throws Exception
     */
    public function isNextDayIsWeekendDay(DateTime $dt): bool
    {
        if ($dt->format(self::WEEK_DAY_FORMAT) >= 5) {
            return true;
        }

        return false;
    }

    /**
     * @param int $estimatedTime The integer value from user. The number how many hours should need solve the issue.
     *
     * @return DateTime
     *
     * @throws Exception
     */
    public function calculateMultipleWorkingDays(int $estimatedTime): DateTime
    {
        $calculateDate = (new DateTime())::createFromImmutable($this->submittedDateTime);
        $submittedHour = (int)$this->submittedDateTime->format('H');
        $submittedMinutes = (int)$this->submittedDateTime->format('i');
        $workDaysAmount = intdiv($estimatedTime, self::WORKING_HOURS_PER_DAY);
        $remainHour = $estimatedTime % self::WORKING_HOURS_PER_DAY;

        while ($workDaysAmount > 0) {
            if ($this->isNextDayIsWeekendDay($calculateDate)) {
                $calculateDate->add(new \DateInterval('P3D'));
                $workDaysAmount--;
                continue;
            }

            $calculateDate->add(new \DateInterval('P1D'));
            $workDaysAmount--;
        }

        $this->submittedDateTime = (new DateTimeImmutable())::createFromMutable($calculateDate);
        $this->estimatedTime = $remainHour;

        if ($remainHour && $this->canProblemSolvableSameDay()) {
            return $calculateDate->add(new \DateInterval('PT' . $remainHour . 'H'));
        }

        if ($this->isNextDayIsWeekendDay($calculateDate)) {
            $calculateDate->add(new \DateInterval('P3D'))->setTime(self::STARTING_WORK_HOUR, $submittedMinutes);
        } else {
            $calculateDate->add(new \DateInterval('P1D'))->setTime(self::STARTING_WORK_HOUR, $submittedMinutes);
        }

        $remainHour = ($submittedHour + $remainHour) - self::FINISHING_WORK_HOUR;

        if (empty($remainHour) || $remainHour < 0) {
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
     * @param DateTimeImmutable|DateTime $calculatedDate
     */
    public function setCalculatedDate(DateTimeImmutable|DateTime $calculatedDate): void
    {
        $this->calculatedDate = $calculatedDate;
    }

    /**
     * @return int
     */
    public function getEstimatedTime(): int
    {
        return $this->estimatedTime;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getSubmittedDateTime(): DateTimeImmutable
    {
        return $this->submittedDateTime;
    }
}

<?php

namespace App\Http\Controllers;

use Exception;
use App\Exceptions\CalculationException;
use App\Exceptions\ExceptionCase;
use Illuminate\Validation\ValidationException;
use App\Http\Helpers\CalculateTimeDemand;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Routing\Annotation\Route;

class DateCalculateController extends Controller
{
    private const NUMBER_OF_SATURDAY = 6;

    public const WEEK_DAY_FORMAT = 'N';
    public const HOUR_MINUTE_FORMAT = 'H:i';
    public const STARTING_WORK_HOUR = 9;
    public const FINISHING_WORK_HOUR = 17;
    public const WORKING_HOURS_PER_DAY = 8;

    private const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

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

        (new CalculateTimeDemand($this));

        $this->composeSuccessResponse();

        return $this->routeResponse;
    }

    /**
     * @throws ValidationException
     */
    private function validateParameters(Request $request): void
    {
        $validator = Validator::make($request->all(), [
            'submit_time' => 'required|date|date_format:'.self::DATE_TIME_FORMAT,
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

        if ($examineDay >= self::NUMBER_OF_SATURDAY) {
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
     * @param int $estimatedTime
     */
    public function setEstimatedTime(int $estimatedTime): void
    {
        $this->estimatedTime = $estimatedTime;
    }

    /**
     * @param DateTimeImmutable $submittedDateTime
     */
    public function setSubmittedDateTime(DateTimeImmutable $submittedDateTime): void
    {
        $this->submittedDateTime = $submittedDateTime;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getSubmittedDateTime(): DateTimeImmutable
    {
        return $this->submittedDateTime;
    }
}

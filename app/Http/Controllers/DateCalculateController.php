<?php

namespace App\Http\Controllers;

use App\Http\Helpers\CheckSubmittedParams;
use Exception;
use App\Exceptions\CalculationException;
use Illuminate\Validation\ValidationException;
use App\Http\Helpers\CalculateTimeDemand;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\Routing\Annotation\Route;

class DateCalculateController extends Controller
{
    private const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    public const WEEK_DAY_FORMAT = 'N';
    public const STARTING_WORK_HOUR = 9;
    public const FINISHING_WORK_HOUR = 17;
    public const WORKING_HOURS_PER_DAY = 8;

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
        $paramsChecker = new CheckSubmittedParams($this);

        $paramsChecker->validateParameters($request);

        $submittedDate = $request->input('submit_time');
        $this->estimatedTime = $request->input('estimated_time');

        $this->submittedDateTime = (new DateTimeImmutable())::createFromFormat(self::DATE_TIME_FORMAT, $submittedDate);

        $paramsChecker->checkProblemReportedOnWorkingDays();
        $paramsChecker->checkProblemReportedDuringWorkingHours();

        (new CalculateTimeDemand($this));

        $this->composeSuccessResponse();

        return $this->routeResponse;
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

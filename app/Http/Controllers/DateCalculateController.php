<?php

namespace App\Http\Controllers;

use App\Http\Helpers\CalculateMultipleDaysTime;
use App\Http\Helpers\CalculateSameDayTime;
use App\Http\Helpers\CalculateTimeDemand;
use App\Http\Helpers\TimeIncreaser;
use App\Http\Helpers\CheckSubmittedParams;
use Exception;
use App\Exceptions\CalculationException;
use Illuminate\Validation\ValidationException;
use DateTime;
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
    private DateTime $calculatedDate;
    private DateTime $submittedDateTime;

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

        $this->submittedDateTime = (new DateTime())::createFromFormat(self::DATE_TIME_FORMAT, $submittedDate);

        $paramsChecker->checkProblemReportedOnWorkingDays();
        $paramsChecker->checkProblemReportedDuringWorkingHours();

        $this->calculate();

        $this->composeSuccessResponse();

        return $this->routeResponse;
    }

    /**
     * @throws CalculationException
     * @throws Exception
     */
    private function calculate()
    {
        if (CalculateTimeDemand::canProblemSolvableSameDay($this)) {
            (new CalculateSameDayTime($this))->runCalculation();
        } else {
            (new CalculateMultipleDaysTime($this))->runCalculation();
        }
    }

    private function composeSuccessResponse(): void
    {
        $this->routeResponse = response()->json(['data' => [
            'due_date' => $this->calculatedDate->format(DateTimeInterface::ATOM),
        ]], \Symfony\Component\HttpFoundation\Response::HTTP_OK);
    }

    public function setCalculatedDate(DateTime $calculatedDate): void
    {
        $this->calculatedDate = $calculatedDate;
    }

    public function getEstimatedTime(): int
    {
        return $this->estimatedTime;
    }

    public function setEstimatedTime(int $estimatedTime): void
    {
        $this->estimatedTime = $estimatedTime;
    }

    public function setSubmittedDateTime(DateTime $submittedDateTime): void
    {
        $this->submittedDateTime = $submittedDateTime;
    }

    public function getSubmittedDateTime(): DateTime
    {
        return $this->submittedDateTime;
    }
}

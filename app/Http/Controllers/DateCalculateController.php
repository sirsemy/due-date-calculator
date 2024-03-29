<?php

namespace App\Http\Controllers;

use App\Http\Executors\MultipleDaysTimeCalculator;
use App\Http\Executors\SameDayTimeCalculator;
use App\Http\Executors\TimeDemandCalculator;
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

        $this->submittedDateTime = (new DateTime())::createFromFormat(config('formats.date_time_format'), $submittedDate);

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
        if (TimeDemandCalculator::canProblemSolvableSameDay($this)) {
            (new SameDayTimeCalculator($this))->runCalculation();
        } else {
            (new MultipleDaysTimeCalculator($this))->runCalculation();
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

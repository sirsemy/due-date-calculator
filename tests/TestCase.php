<?php

namespace Tests;

use App\Http\Controllers\DateCalculateController;
use DateTime;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Carbon;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * @var DateTimeImmutable
     */
    protected DateTimeImmutable $dateTime;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dateTime = new DateTimeImmutable();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->dateTime);
    }
}

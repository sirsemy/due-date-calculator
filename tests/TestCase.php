<?php

namespace Tests;

use App\Http\Controllers\DateCalculateController;
use DateTime;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Carbon;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * @var DateTime
     */
    protected DateTime $dateTime;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dateTime = new DateTime();
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

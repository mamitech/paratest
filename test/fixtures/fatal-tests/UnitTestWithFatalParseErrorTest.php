<?php

declare(strict_types=1);

require_once __DIR__ . '/../failing-tests/UnitTestWithMethodAnnotationsTest.php';

/**
 * @runParallel
 */
class UnitTestWithFatalParseErrorTest extends UnitTestWithMethodAnnotationsTest
{
    /**
     * @group fixtures
     */
    public function testTruth(): void
    {
        I will fail fataly because this is not a php statement .
    }

    /**
     * @test
     */
    public function isItFalse(): void
    {
        sleep(2);
        $this->assertFalse(false);
    }
}

<?php

declare(strict_types=1);

namespace ParaTest\Tests\Functional;

use function getcwd;

class OutputTest extends FunctionalTestBase
{
    /** @var ParaTestInvoker */
    protected $paratest;

    public function setUp(): void
    {
        parent::setUp();
        $this->paratest = new ParaTestInvoker(
            $this->fixture('failing-tests/UnitTestWithClassAnnotationTest.php'),
            BOOTSTRAP
        );
    }

    public function testDefaultMessagesDisplayed(): void
    {
        $output = $this->paratest->execute(['p' => 5])->getOutput();
        $this->assertStringContainsString('Running phpunit in 5 processes with ' . PHPUNIT, $output);
        $this->assertStringContainsString('Configuration read from ' . getcwd() . DS . 'phpunit.xml.dist', $output);
        $this->assertMatchesRegularExpression('/[.F]{4}/', $output);
    }

    public function testMessagePrintedWhenInvalidConfigFileSupplied(): void
    {
        $output = $this->paratest
            ->execute(['configuration' => 'nope.xml'])
            ->getOutput();
        $this->assertStringContainsString('Could not read "nope.xml"', $output);
    }

    public function testMessagePrintedWhenFunctionalModeIsOn(): void
    {
        $output = $this->paratest
            ->execute(['functional', 'p' => 5])
            ->getOutput();
        $this->assertStringContainsString('Running phpunit in 5 processes with ' . PHPUNIT, $output);
        $this->assertStringContainsString('Functional mode is ON.', $output);
        $this->assertMatchesRegularExpression('/[.F]{4}/', $output);
    }

    public function testProcCountIsReportedWithProcOption(): void
    {
        $output = $this->paratest->execute(['p' => 1])
            ->getOutput();
        $this->assertStringContainsString('Running phpunit in 1 process with ' . PHPUNIT, $output);
        $this->assertMatchesRegularExpression('/[.F]{4}/', $output);
    }
}

<?php

declare(strict_types=1);

namespace ParaTest\Tests\Unit\Logging\JUnit;

use InvalidArgumentException;
use ParaTest\Logging\JUnit\Reader;
use ParaTest\Logging\JUnit\TestSuite;
use ParaTest\Tests\TestBase;
use PHPUnit\Framework\ExpectationFailedException;
use stdClass;

use function file_get_contents;
use function file_put_contents;

class ReaderTest extends TestBase
{
    /** @var string  */
    protected $mixedPath;
    /** @var Reader  */
    protected $mixed;
    /** @var Reader  */
    protected $single;
    /** @var Reader  */
    protected $empty;
    /** @var Reader  */
    protected $multi_errors;

    public function setUp(): void
    {
        $this->mixedPath    = FIXTURES . DS . 'results' . DS . 'mixed-results.xml';
        $this->mixed        = new Reader($this->mixedPath);
        $single             = FIXTURES . DS . 'results' . DS . 'single-wfailure.xml';
        $this->single       = new Reader($single);
        $empty              = FIXTURES . DS . 'results' . DS . 'empty-test-suite.xml';
        $this->empty        = new Reader($empty);
        $multi_errors       = FIXTURES . DS . 'results' . DS . 'multiple-errors-with-system-out.xml';
        $this->multi_errors = new Reader($multi_errors);
    }

    public function testInvalidPathThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $reader = new Reader('/path/to/nowhere');
    }

    public function testIsSingleSuiteReturnsTrueForSingleSuite(): void
    {
        $this->assertTrue($this->single->isSingleSuite());
    }

    public function testIsSingleSuiteReturnsFalseForMultipleSuites(): void
    {
        $this->assertFalse($this->mixed->isSingleSuite());
    }

    public function testMixedSuiteShouldConstructRootSuite(): TestSuite
    {
        $suites = $this->mixed->getSuites();
        $this->assertCount(1, $suites);
        $this->assertEquals('test/fixtures/tests/', $suites[0]->name);
        $this->assertEquals('7', $suites[0]->tests);
        $this->assertEquals('6', $suites[0]->assertions);
        $this->assertEquals('2', $suites[0]->failures);
        $this->assertEquals('1', $suites[0]->errors);
        $this->assertEquals('0.007625', $suites[0]->time);

        return $suites[0];
    }

    /**
     * @depends testMixedSuiteShouldConstructRootSuite
     */
    public function testMixedSuiteConstructsChildSuites(TestSuite $suite): TestSuite
    {
        $this->assertCount(3, $suite->suites);
        $first = $suite->suites[0];
        $this->assertEquals('UnitTestWithClassAnnotationTest', $first->name);
        $this->assertEquals(
            '/home/brian/Projects/parallel-phpunit/test/fixtures/tests/UnitTestWithClassAnnotationTest.php',
            $first->file
        );
        $this->assertEquals('3', $first->tests);
        $this->assertEquals('3', $first->assertions);
        $this->assertEquals('1', $first->failures);
        $this->assertEquals('0', $first->errors);
        $this->assertEquals('0.006109', $first->time);

        return $first;
    }

    /**
     * @depends testMixedSuiteConstructsChildSuites
     */
    public function testMixedSuiteConstructsTestCases(TestSuite $suite): void
    {
        $this->assertCount(3, $suite->cases);
        $first = $suite->cases[0];
        $this->assertEquals('testTruth', $first->name);
        $this->assertEquals('UnitTestWithClassAnnotationTest', $first->class);
        $this->assertEquals(
            '/home/brian/Projects/parallel-phpunit/test/fixtures/tests/UnitTestWithClassAnnotationTest.php',
            $first->file
        );
        $this->assertEquals('10', $first->line);
        $this->assertEquals('1', $first->assertions);
        $this->assertEquals('0.001760', $first->time);
    }

    public function testMixedSuiteCasesLoadFailures(): void
    {
        $suites = $this->mixed->getSuites();
        $case   = $suites[0]->suites[0]->cases[1];
        $this->assertCount(1, $case->failures);
        $failure = $case->failures[0];
        $this->assertEquals(ExpectationFailedException::class, $failure['type']);
        $this->assertEquals(
            "UnitTestWithClassAnnotationTest::testFalsehood\nFailed asserting that true is false.\n\n" .
            '/home/brian/Projects/parallel-phpunit/test/fixtures/tests/UnitTestWithClassAnnotationTest.php:20',
            $failure['text']
        );
    }

    public function testMixedSuiteCasesLoadErrors(): void
    {
        $suites = $this->mixed->getSuites();
        $case   = $suites[0]->suites[1]->cases[0];
        $this->assertCount(1, $case->errors);
        $error = $case->errors[0];
        $this->assertEquals('Exception', $error['type']);
        $this->assertEquals(
            "UnitTestWithErrorTest::testTruth\nException: Error!!!\n\n" .
                '/home/brian/Projects/parallel-phpunit/test/fixtures/tests/UnitTestWithErrorTest.php:12',
            $error['text']
        );
    }

    public function testSingleSuiteShouldConstructRootSuite(): TestSuite
    {
        $suites = $this->single->getSuites();
        $this->assertCount(1, $suites);
        $this->assertEquals('UnitTestWithMethodAnnotationsTest', $suites[0]->name);
        $this->assertEquals(
            '/home/brian/Projects/parallel-phpunit/test/fixtures/tests/UnitTestWithMethodAnnotationsTest.php',
            $suites[0]->file
        );
        $this->assertEquals('3', $suites[0]->tests);
        $this->assertEquals('3', $suites[0]->assertions);
        $this->assertEquals('1', $suites[0]->failures);
        $this->assertEquals('0', $suites[0]->errors);
        $this->assertEquals('0.005895', $suites[0]->time);

        return $suites[0];
    }

    /**
     * @param mixed $suite
     *
     * @depends testSingleSuiteShouldConstructRootSuite
     */
    public function testSingleSuiteShouldHaveNoChildSuites($suite): void
    {
        $this->assertCount(0, $suite->suites);
    }

    /**
     * @param mixed $suite
     *
     * @depends testSingleSuiteShouldConstructRootSuite
     */
    public function testSingleSuiteConstructsTestCases($suite): void
    {
        $this->assertCount(3, $suite->cases);
        $first = $suite->cases[0];
        $this->assertEquals('testTruth', $first->name);
        $this->assertEquals('UnitTestWithMethodAnnotationsTest', $first->class);
        $this->assertEquals(
            '/home/brian/Projects/parallel-phpunit/test/fixtures/tests/UnitTestWithMethodAnnotationsTest.php',
            $first->file
        );
        $this->assertEquals('7', $first->line);
        $this->assertEquals('1', $first->assertions);
        $this->assertEquals('0.001632', $first->time);
    }

    public function testSingleSuiteCasesLoadFailures(): void
    {
        $suites = $this->single->getSuites();
        $case   = $suites[0]->cases[1];
        $this->assertCount(1, $case->failures);
        $failure = $case->failures[0];
        $this->assertEquals(ExpectationFailedException::class, $failure['type']);
        $this->assertEquals(
            "UnitTestWithMethodAnnotationsTest::testFalsehood\nFailed asserting that true is false.\n\n" .
                '/home/brian/Projects/parallel-phpunit/test/fixtures/tests/UnitTestWithMethodAnnotationsTest.php:18',
            $failure['text']
        );
    }

    public function testEmptySuiteConstructsTestCase(): void
    {
        $suites = $this->empty->getSuites();
        $this->assertCount(1, $suites);

        $suite = $suites[0];
        $this->assertEquals('', $suite->name);
        $this->assertEquals('', $suite->file);
        $this->assertEquals(0, $suite->tests);
        $this->assertEquals(0, $suite->assertions);
        $this->assertEquals(0, $suite->failures);
        $this->assertEquals(0, $suite->errors);
        $this->assertEquals(0, $suite->time);
    }

    public function testMixedGetTotals(): void
    {
        $this->assertEquals(7, $this->mixed->getTotalTests());
        $this->assertEquals(6, $this->mixed->getTotalAssertions());
        $this->assertEquals(2, $this->mixed->getTotalFailures());
        $this->assertEquals(1, $this->mixed->getTotalErrors());
        $this->assertEquals(0.007625, $this->mixed->getTotalTime());
    }

    public function testSingleGetTotals(): void
    {
        $this->assertEquals(3, $this->single->getTotalTests());
        $this->assertEquals(3, $this->single->getTotalAssertions());
        $this->assertEquals(1, $this->single->getTotalFailures());
        $this->assertEquals(0, $this->single->getTotalErrors());
        $this->assertEquals(0.005895, $this->single->getTotalTime());
    }

    public function testMixedGetFailureMessages(): void
    {
        $failures = $this->mixed->getFailures();
        $this->assertCount(2, $failures);
        $this->assertEquals(
            "UnitTestWithClassAnnotationTest::testFalsehood\nFailed asserting that true is false.\n\n" .
                '/home/brian/Projects/parallel-phpunit/test/fixtures/tests/UnitTestWithClassAnnotationTest.php:20',
            $failures[0]
        );
        $this->assertEquals(
            "UnitTestWithMethodAnnotationsTest::testFalsehood\nFailed asserting that true is false." .
                "\n\n/home/brian/Projects/parallel-phpunit/test/fixtures/tests/UnitTestWithMethodAnnotationsTest." .
                'php:18',
            $failures[1]
        );
    }

    public function testMixedGetErrorMessages(): void
    {
        $errors = $this->mixed->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals(
            "UnitTestWithErrorTest::testTruth\nException: Error!!!\n\n" .
                '/home/brian/Projects/parallel-phpunit/test/fixtures/tests/UnitTestWithErrorTest.php:12',
            $errors[0]
        );
    }

    public function testSingleGetMessages(): void
    {
        $failures = $this->single->getFailures();
        $this->assertCount(1, $failures);
        $this->assertEquals(
            "UnitTestWithMethodAnnotationsTest::testFalsehood\nFailed asserting that true is false.\n\n" .
                '/home/brian/Projects/parallel-phpunit/test/fixtures/tests/UnitTestWithMethodAnnotationsTest.php:18',
            $failures[0]
        );
    }

    /**
     * https://github.com/paratestphp/paratest/issues/352
     */
    public function testGetMultiErrorsMessages(): void
    {
        $errors = $this->multi_errors->getErrors();
        $this->assertCount(2, $errors);
        $this->assertEquals(
            "Risky Test\n" .
            "/project/vendor/phpunit/phpunit/src/TextUI/Command.php:200\n" .
            "/project/vendor/phpunit/phpunit/src/TextUI/Command.php:159\n" .
            'Custom error log on result test with multiple errors!',
            $errors[0]
        );
        $this->assertEquals(
            "Risky Test\n" .
            "/project/vendor/phpunit/phpunit/src/TextUI/Command.php:200\n" .
            "/project/vendor/phpunit/phpunit/src/TextUI/Command.php:159\n" .
            'Custom error log on result test with multiple errors!',
            $errors[1]
        );
    }

    public function testMixedGetFeedback(): void
    {
        $feedback = $this->mixed->getFeedback();
        $this->assertEquals(['.', 'F', '.', 'E', '.', 'F', '.'], $feedback);
    }

    public function testRemoveLog(): void
    {
        $contents = file_get_contents($this->mixedPath);
        $tmp      = FIXTURES . DS . 'results' . DS . 'dummy.xml';
        file_put_contents($tmp, $contents);
        $reader = new Reader($tmp);
        $reader->removeLog();
        $this->assertFileDoesNotExist($tmp);
    }

    /**
     * Extraction of log from xml file to use in test of validation "SystemOut" result.
     *
     * @return stdClass $log
     */
    public static function extractLog(): stdClass
    {
        $log          = new stdClass();
        $result       = FIXTURES . DS . 'results' . DS . 'mixed-results-with-system-out.xml';
        $node         = new Reader($result);
        $log->failure = $node->getSuites()[0]->suites[0]->cases[1]->failures[0]['text'];
        $log->error   = $node->getSuites()[0]->suites[1]->cases[0]->errors[0]['text'];

        return $log;
    }

    public function testResultWithSystemOut(): void
    {
        $customLog   = "\nCustom error log on result test with ";
        $result      = FIXTURES . DS . 'results' . DS . 'mixed-results-with-system-out.xml';
        $failLog     = self::extractLog()->failure . $customLog . 'failure!';
        $errorLog    = self::extractLog()->error . $customLog . 'error!';
        $node        = new Reader($result);
        $resultFail  = $node->getSuites()[0]->suites[2]->cases[1]->failures[0]['text'];
        $resultError = $node->getSuites()[0]->suites[1]->cases[1]->errors[0]['text'];

        $this->assertEquals($failLog, $resultFail);
        $this->assertEquals($errorLog, $resultError);
    }
}

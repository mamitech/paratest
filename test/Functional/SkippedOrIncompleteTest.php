<?php

declare(strict_types=1);

namespace ParaTest\Tests\Functional;

/**
 * @todo SkippedOrIncompleteTest can't be used in default mode with group filter
 *       (not implemented yet) so we have to split tests per file.
 */
class SkippedOrIncompleteTest extends FunctionalTestBase
{
    /** @var ParaTestInvoker */
    private $invoker;

    public function setUp(): void
    {
        parent::setUp();
        $this->invoker = new ParaTestInvoker(
            $this->fixture('skipped-tests/SkippedOrIncompleteTest.php'),
            BOOTSTRAP
        );
    }

    public function testSkippedInFunctionalMode()
    {
        $proc = $this->invoker->execute([
            'functional' => null,
            'filter' => 'testSkipped',
        ]);

        $expected = "OK, but incomplete, skipped, or risky tests!\n"
            . 'Tests: 1, Assertions: 0, Incomplete: 1.';
        $this->assertStringContainsString($expected, $proc->getOutput());

        $this->assertContainsNSkippedTests(1, $proc->getOutput());
    }

    public function testIncompleteInFunctionalMode()
    {
        $proc = $this->invoker->execute([
            'functional' => null,
            'filter' => 'testIncomplete',
        ]);

        $expected = "OK, but incomplete, skipped, or risky tests!\n"
            . 'Tests: 1, Assertions: 0, Incomplete: 1.';
        $this->assertStringContainsString($expected, $proc->getOutput());

        $this->assertContainsNSkippedTests(1, $proc->getOutput());
    }

    public function testDataProviderWithSkippedInFunctionalMode()
    {
        $proc = $this->invoker->execute([
            'functional' => null,
            'max-batch-size' => 50,
            'filter' => 'testDataProviderWithSkipped',
        ]);

        $expected = "OK, but incomplete, skipped, or risky tests!\n"
            . 'Tests: 100, Assertions: 33, Incomplete: 67.';
        $this->assertStringContainsString($expected, $proc->getOutput());
        $this->assertContainsNSkippedTests(67, $proc->getOutput());
    }

    public function testSkippedInDefaultMode()
    {
        // amount of tests is known, based on amount of methods, so
        // we can identify skipped tests

        $this->invoker = new ParaTestInvoker(
            $this->fixture('skipped-tests/SkippedTest.php'),
            BOOTSTRAP
        );

        $proc = $this->invoker->execute();

        $expected = "OK, but incomplete, skipped, or risky tests!\n"
            . 'Tests: 1, Assertions: 0, Incomplete: 1.';
        $this->assertStringContainsString($expected, $proc->getOutput());
        $this->assertContainsNSkippedTests(1, $proc->getOutput());
    }

    public function testIncompleteInDefaultMode()
    {
        // amount of tests is known, based on amount of methods, so
        // we can identify skipped tests

        $this->invoker = new ParaTestInvoker(
            $this->fixture('skipped-tests/IncompleteTest.php'),
            BOOTSTRAP
        );

        $proc = $this->invoker->execute();

        // TODO: What happened to the incomplete test?
        $expected = "OK, but incomplete, skipped, or risky tests!\n"
            . 'Tests: 1, Assertions: 0, Incomplete: 1.';
        $this->assertStringContainsString($expected, $proc->getOutput());
        $this->assertContainsNSkippedTests(1, $proc->getOutput());
    }

    public function testDataProviderWithSkippedInDefaultMode()
    {
        // TODO: update comments
        // amount of tests is known, but based on amount of methods,
        // but test has more actual tests from data provider so
        // we can't identify skipped tests

        $this->invoker = new ParaTestInvoker(
            $this->fixture('skipped-tests/SkippedAndIncompleteDataProviderTest.php'),
            BOOTSTRAP
        );

        $proc = $this->invoker->execute();

        $expected = "OK, but incomplete, skipped, or risky tests!\nTests: 100, Assertions: 33, Incomplete: 67.";
        $this->assertStringContainsString($expected, $proc->getOutput());
    }

    protected function assertContainsNSkippedTests($n, $output)
    {
        preg_match('/\n\n([\.ISEF].*)\n\nTime/s', $output, $matches);
        $this->assertCount(2, $matches);
        $numberOfS = substr_count($matches[1], 'S');
        $this->assertEquals(
            $n,
            $numberOfS,
            "The test should have skipped $n tests, instead it skipped $numberOfS, $matches[1]"
        );
    }
}

<?php

declare(strict_types=1);

namespace ParaTest\Tests\Functional\Runners\PHPUnit;

use ParaTest\Runners\PHPUnit\Runner;
use ParaTest\Tests\TestBase;

use function count;
use function file_exists;
use function glob;
use function ob_end_clean;
use function ob_start;
use function simplexml_load_file;
use function sys_get_temp_dir;
use function unlink;

class RunnerIntegrationTest extends TestBase
{
    /** @var Runner $runner */
    protected $runner;
    /** @var array */
    protected $options;

    protected function setUp(): void
    {
        $this->skipIfCodeCoverageNotEnabled();

        $this->options = [
            'path' => FIXTURES . DS . 'failing-tests',
            'phpunit' => PHPUNIT,
            'coverage-php' => sys_get_temp_dir() . DS . 'testcoverage.php',
            'bootstrap' => BOOTSTRAP,
            'whitelist' => FIXTURES . DS . 'failing-tests',
        ];
        $this->runner  = new Runner($this->options);
    }

    protected function tearDown(): void
    {
        $testcoverageFile = sys_get_temp_dir() . DS . 'testcoverage.php';
        if (file_exists($testcoverageFile)) {
            unlink($testcoverageFile);
        }

        parent::tearDown();
    }

    private function globTempDir($pattern)
    {
        return glob(sys_get_temp_dir() . DS . $pattern);
    }

    public function testRunningTestsShouldLeaveNoTempFiles(): void
    {
        $countBefore         = count($this->globTempDir('PT_*'));
        $countCoverageBefore = count($this->globTempDir('CV_*'));

        ob_start();
        $this->runner->run();
        ob_end_clean();

        $countAfter         = count($this->globTempDir('PT_*'));
        $countCoverageAfter = count($this->globTempDir('CV_*'));

        $this->assertEquals(
            $countAfter,
            $countBefore,
            "Test Runner failed to clean up the 'PT_*' file in " . sys_get_temp_dir()
        );
        $this->assertEquals(
            $countCoverageAfter,
            $countCoverageBefore,
            "Test Runner failed to clean up the 'CV_*' file in " . sys_get_temp_dir()
        );
    }

    public function testLogJUnitCreatesXmlFile(): void
    {
        $outputPath                 = FIXTURES . DS . 'logs' . DS . 'test-output.xml';
        $this->options['log-junit'] = $outputPath;
        $runner                     = new Runner($this->options);

        ob_start();
        $runner->run();
        ob_end_clean();

        $this->assertFileExists($outputPath);
        $this->assertJunitXmlIsCorrect($outputPath);
        if (! file_exists($outputPath)) {
            return;
        }

        unlink($outputPath);
    }

    public function assertJunitXmlIsCorrect($path): void
    {
        $doc      = simplexml_load_file($path);
        $suites   = $doc->xpath('//testsuite');
        $cases    = $doc->xpath('//testcase');
        $failures = $doc->xpath('//failure');
        $errors   = $doc->xpath('//error');

        // these numbers represent the tests in fixtures/failing-tests
        // so will need to be updated when tests are added or removed
        $this->assertCount(6, $suites);
        $this->assertCount(16, $cases);
        $this->assertCount(6, $failures);
        $this->assertCount(1, $errors);
    }
}

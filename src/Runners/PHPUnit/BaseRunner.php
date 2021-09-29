<?php

declare(strict_types=1);

namespace ParaTest\Runners\PHPUnit;

use ParaTest\Coverage\CoverageMerger;
use ParaTest\Logging\JUnit\Writer;
use ParaTest\Logging\LogInterpreter;
use ParaTest\Parser\ParsedFunction;

use function array_merge;
use function file_exists;
use function getenv;
use function putenv;
use function sprintf;

abstract class BaseRunner
{
    /** @var Options */
    protected $options;

    /** @var LogInterpreter */
    protected $interpreter;

    /** @var ResultPrinter */
    protected $printer;

    /**
     * A collection of pending ExecutableTest objects that have
     * yet to run.
     *
     * @var array<int|string, ExecutableTest|TestMethod|ParsedFunction>
     */
    protected $pending = [];

    /**
     * A collection of ExecutableTest objects that have processes
     * currently running.
     *
     * @var array|ExecutableTest[]
     */
    protected $running = [];

    /**
     * A tallied exit code that returns the highest exit
     * code returned out of the entire collection of tests.
     *
     * @var int
     */
    protected $exitcode = -1;

    /**
     * CoverageMerger to hold track of the accumulated coverage.
     *
     * @var CoverageMerger
     */
    protected $coverage = null;

    /**
     * @param array<string, string|bool|int> $opts
     */
    public function __construct(array $opts = [])
    {
        $this->options     = new Options($opts);
        $this->interpreter = new LogInterpreter();
        $this->printer     = new ResultPrinter($this->interpreter);
    }

    public function run(): void
    {
        $this->initialize();
    }

    /**
     * Ensures a valid configuration was supplied. If not
     * causes ParaTest to print the error message and exit immediately
     * with an exit code of 1.
     */
    protected function verifyConfiguration(): void
    {
        if (
            isset($this->options->filtered['configuration']) &&
            ! file_exists($path = $this->options->filtered['configuration']->getPath())
        ) {
            $this->printer->println(sprintf('Could not read "%s".', $path));
            exit(1);
        }
    }

    /**
     * Builds the collection of pending ExecutableTest objects
     * to run. If functional mode is enabled $this->pending will
     * contain a collection of TestMethod objects instead of Suite
     * objects.
     */
    protected function load(SuiteLoader $loader): void
    {
        $loader->load($this->options->path);
        $executables   = $this->options->functional ? $loader->getTestMethods() : $loader->getSuites();
        $this->pending = array_merge($this->pending, $executables);
        foreach ($this->pending as $pending) {
            $this->printer->addTest($pending);
        }
    }

    /**
     * Returns the highest exit code encountered
     * throughout the course of test execution.
     */
    public function getExitCode(): int
    {
        return $this->exitcode;
    }

    /**
     * Write output to JUnit format if requested.
     */
    protected function log(): void
    {
        if (! isset($this->options->filtered['log-junit'])) {
            return;
        }

        $output = $this->options->filtered['log-junit'];
        $writer = new Writer($this->interpreter, $this->options->path);
        $writer->write($output);
    }

    /**
     * Write coverage to file if requested.
     */
    protected function logCoverage(): void
    {
        if (! $this->hasCoverage()) {
            return;
        }

        $filteredOptions = $this->options->filtered;

        $reporter = $this->getCoverage()->getReporter();

        if (isset($filteredOptions['coverage-clover'])) {
            $reporter->clover($filteredOptions['coverage-clover']);
        }

        if (isset($filteredOptions['coverage-crap4j'])) {
            $reporter->crap4j($filteredOptions['coverage-crap4j']);
        }

        if (isset($filteredOptions['coverage-html'])) {
            $reporter->html($filteredOptions['coverage-html']);
        }

        if (isset($filteredOptions['coverage-text'])) {
            $reporter->text();
        }

        if (isset($filteredOptions['coverage-xml'])) {
            $reporter->xml($filteredOptions['coverage-xml']);
        }

        $reporter->php($filteredOptions['coverage-php']);
    }

    protected function initCoverage(): void
    {
        if (! isset($this->options->filtered['coverage-php'])) {
            return;
        }

        $this->coverage = new CoverageMerger((int) $this->options->coverageTestLimit);
    }

    protected function hasCoverage(): bool
    {
        return $this->getCoverage() !== null;
    }

    protected function getCoverage(): ?CoverageMerger
    {
        return $this->coverage;
    }

    /**
     * Overrides envirenment variables if needed.
     */
    protected function overrideEnvironmentVariables(): void
    {
        if (! isset($this->options->filtered['configuration'])) {
            return;
        }

        $variables = $this->options->filtered['configuration']->getEnvironmentVariables();

        foreach ($variables as $key => $value) {
            putenv(sprintf('%s=%s', $key, getenv($key, true) ?: $value));

            $_ENV[$key] = getenv($key, true) ?: $value;
        }
    }

    protected function initialize(): void
    {
        $this->verifyConfiguration();
        $this->overrideEnvironmentVariables();
        $this->initCoverage();
        $this->load(new SuiteLoader($this->options));
        $this->printer->start($this->options);
    }
}

<?php

declare(strict_types=1);

namespace ParaTest\Logging;

use ParaTest\Logging\JUnit\Reader;
use ParaTest\Logging\JUnit\TestCase;
use ParaTest\Logging\JUnit\TestSuite;

use function array_merge;
use function array_reduce;
use function array_values;
use function assert;
use function count;
use function reset;
use function ucfirst;

class LogInterpreter extends MetaProvider
{
    /**
     * A collection of Reader objects
     * to aggregate results from.
     *
     * @var Reader[]
     */
    protected $readers = [];

    /**
     * Reset the array pointer of the internal
     * readers collection.
     */
    public function rewind(): void
    {
        reset($this->readers);
    }

    /**
     * Add a new Reader to be included
     * in the final results.
     *
     * @return $this
     */
    public function addReader(Reader $reader): self
    {
        $this->readers[] = $reader;

        return $this;
    }

    /**
     * Return all Reader objects associated
     * with the LogInterpreter.
     *
     * @return Reader[]
     */
    public function getReaders(): array
    {
        return $this->readers;
    }

    /**
     * Returns true if total errors and failures
     * equals 0, false otherwise
     * TODO: Remove this comment if we don't care about skipped tests in callers.
     */
    public function isSuccessful(): bool
    {
        $failures = $this->getNumericValue('failures');
        $errors   = $this->getNumericValue('errors');

        return $failures === 0 && $errors === 0;
    }

    /**
     * Get all test case objects found within
     * the collection of Reader objects.
     *
     * @return TestCase[]
     */
    public function getCases(): array
    {
        $cases = [];
        foreach ($this->readers as $reader) {
            foreach ($reader->getSuites() as $suite) {
                $cases = array_merge($cases, $suite->cases);
                foreach ($suite->suites as $nested) {
                    $this->extendEmptyCasesFromSuites($nested->cases, $suite);
                    $cases = array_merge($cases, $nested->cases);
                }
            }
        }

        return $cases;
    }

    /**
     * Fix problem with empty testcase from DataProvider.
     *
     * @param TestCase[] $cases
     */
    protected function extendEmptyCasesFromSuites(array $cases, TestSuite $suite): void
    {
        $class = $suite->name;
        $file  = $suite->file;

        foreach ($cases as $case) {
            assert($case instanceof TestCase);
            if (empty($case->class)) {
                $case->class = $class;
            }

            if (! empty($case->file)) {
                continue;
            }

            $case->file = $file;
        }
    }

    /**
     * Flattens all cases into their respective suites.
     *
     * @return TestSuite[] $suites a collection of suites and their cases
     */
    public function flattenCases(): array
    {
        $dict = [];
        foreach ($this->getCases() as $case) {
            if (! isset($dict[$case->file])) {
                $dict[$case->file] = new TestSuite($case->class, 0, 0, 0, 0, 0, 0);
            }

            $dict[$case->file]->cases[] = $case;
            ++$dict[$case->file]->tests;
            $dict[$case->file]->assertions += $case->assertions;
            $dict[$case->file]->failures   += count($case->failures);
            $dict[$case->file]->errors     += count($case->errors);
            $dict[$case->file]->skipped    += count($case->skipped);
            $dict[$case->file]->time       += $case->time;
            $dict[$case->file]->file        = $case->file;
        }

        return array_values($dict);
    }

    /**
     * Returns a value as either a float or int.
     *
     * @return float|int
     */
    protected function getNumericValue(string $property)
    {
        return $property === 'time'
               ? (float) $this->accumulate('getTotalTime')
               : (int) $this->accumulate('getTotal' . ucfirst($property));
    }

    /**
     * Gets messages of a given type and
     * merges them into a single collection.
     *
     * @return string[]
     */
    protected function getMessages(string $type): array
    {
        return $this->mergeMessages('get' . ucfirst($type));
    }

    /**
     * Flatten messages into a single collection
     * based on an accessor method.
     *
     * @return string[]
     */
    private function mergeMessages(string $method): array
    {
        $messages = [];
        foreach ($this->readers as $reader) {
            $messages = array_merge($messages, $reader->{$method}());
        }

        return $messages;
    }

    /**
     * Reduces a collection of readers down to a single
     * result based on an accessor.
     *
     * @return mixed
     */
    private function accumulate(string $method)
    {
        return array_reduce($this->readers, static function ($result, $reader) use ($method) {
            $result += $reader->$method();

            return $result;
        }, 0);
    }
}

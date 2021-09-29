<?php

declare(strict_types=1);

namespace ParaTest\Logging\JUnit;

use PHPUnit\Framework\RiskyTestError;
use SimpleXMLElement;

use function assert;
use function class_exists;
use function is_subclass_of;
use function iterator_to_array;
use function trim;

/**
 * A simple data structure for tracking
 * the results of a testcase node in a
 * JUnit xml document
 *
 * @internal
 */
final class TestCase
{
    /** @var string */
    public $name;

    /** @var string */
    public $class;

    /** @var string */
    public $file;

    /** @var int */
    public $line;

    /** @var int */
    public $assertions;

    /** @var float */
    public $time;

    /** @var array<int, array{type: string, text: string}> */
    public $errors = [];

    /** @var array<int, array{type: string, text: string}> */
    public $failures = [];

    /** @var array<int, array{type: string, text: string}> */
    public $warnings = [];

    /** @var array<int, array{type: string, text: string}> */
    public $skipped = [];

    /** @var array<int, array{type: string, text: string}> */
    public $risky = [];

    public function __construct(
        string $name,
        string $class,
        string $file,
        int $line,
        int $assertions,
        float $time
    ) {
        $this->name       = $name;
        $this->class      = $class;
        $this->file       = $file;
        $this->line       = $line;
        $this->assertions = $assertions;
        $this->time       = $time;
    }

    /**
     * Factory method that creates a TestCase object
     * from a SimpleXMLElement.
     *
     * @return TestCase
     */
    public static function caseFromNode(SimpleXMLElement $node): self
    {
        $case = new self(
            (string) $node['name'],
            (string) $node['class'],
            (string) $node['file'],
            (int) $node['line'],
            (int) $node['assertions'],
            (float) $node['time']
        );

        $system_output = $node->{'system-out'};

        /** @var SimpleXMLElement[] $errors */
        $errors = (array) $node->xpath('error');
        $risky  = [];
        foreach ($errors as $index => $error) {
            $attributes = $error->attributes();
            assert($attributes !== null);
            $attributes = iterator_to_array($attributes);
            $type       = (string) $attributes['type'];
            if (
                ! class_exists($type)
                || ! ($type === RiskyTestError::class || is_subclass_of($type, RiskyTestError::class))
            ) {
                continue;
            }

            unset($errors[$index]);
            $risky[] = $error;
        }

        $defect_groups = [
            'failures' => (array) $node->xpath('failure'),
            'errors' => $errors,
            'warnings' => (array) $node->xpath('warning'),
            'skipped' => (array) $node->xpath('skipped'),
            'risky' => $risky,
        ];

        foreach ($defect_groups as $group => $defects) {
            foreach ($defects as $defect) {
                assert($defect !== false);

                $message  = (string) $defect;
                $message .= (string) $system_output;

                $case->{$group}[] = [
                    'type' => (string) $defect['type'],
                    'text' => trim($message),
                ];
            }
        }

        return $case;
    }
}

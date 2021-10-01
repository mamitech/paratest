<?php

declare(strict_types=1);

namespace ParaTest\Tests\Functional;

use Habitat\Habitat;
use Symfony\Component\Process\Process;

use function defined;
use function is_callable;
use function is_numeric;
use function sprintf;
use function strlen;

use const PHP_BINARY;

class ParaTestInvoker
{
    public $path;
    public $bootstrap;

    public function __construct($path, $bootstrap)
    {
        $this->path      = $path;
        $this->bootstrap = $bootstrap;
    }

    /**
     * Runs the command, returns the proc after it's done.
     *
     * @param array $options
     */
    public function execute(array $options = [], ?callable $callback = null): Process
    {
        $cmd  = $this->buildCommand($options);
        $env  = defined('PHP_WINDOWS_VERSION_BUILD') ? Habitat::getAll() : null;
        $proc = Process::fromShellCommandline($cmd, null, $env, null, $timeout = 600);

        if (! is_callable($callback)) {
            $proc->run();
        } else {
            $proc->run($callback);
        }

        return $proc;
    }

    private function buildCommand($options = [])
    {
        $cmd = sprintf(
            '%s %s --bootstrap %s --phpunit %s',
            PHP_BINARY,
            PARA_BINARY,
            $this->bootstrap,
            PHPUNIT
        );
        foreach ($options as $switch => $value) {
            if (is_numeric($switch)) {
                $switch = $value;
                $value  = null;
            }

            if (strlen($switch) > 1) {
                $switch = '--' . $switch;
            } else {
                $switch = '-' . $switch;
            }

            $cmd .= sprintf(' %s', $value ? $switch . ' ' . $value : $switch);
        }

        $cmd .= sprintf(' %s', $this->path);

        return $cmd;
    }
}

<?php

namespace MAChitgarha\Parvaj\Util;

use Symfony\Component\Console\Exception\RuntimeException;

class Process extends \Symfony\Component\Process\Process
{
    private string $completeOutput = "";

    public function __construct(array $shellArgs)
    {
        // Never end a process, until the user kills it himself
        parent::__construct($shellArgs, null, null, null, null);
    }

    public function run(callable $callback = null, array $env = []): int
    {
        return parent::run(
            isset($callback)
                ? function (string $type, string $output) use ($callback) {
                    $this->completeOutput .= $output;
                    $callback($type, $output);
                }
                : function (string $type, string $output) {
                    $this->completeOutput .= $output;
                }
            ,
            $env
        );
    }

    public function getCompleteOutput(): string
    {
        return $this->completeOutput;
    }

    public function runSafe(): void
    {
        $exitCode = $this->run();

        if ($exitCode !== 0) {
            throw new RuntimeException($this->completeOutput);
        }
    }
}

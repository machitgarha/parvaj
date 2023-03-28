<?php

namespace MAChitgarha\Parvaj\Util;

use Symfony\Component\Process\Process as SymfonyProcess;

class Process
{
    private SymfonyProcess $process;

    public function __construct(array $shellArgs)
    {
        // Never end a process, until the user kills it himself
        $this->process = new SymfonyProcess($shellArgs, null, null, null, null);
    }

    public function run(array $env = []): int
    {
        $completeOutput = "";

        $exitCode = $this->process->run(function ($x, string $output) use (&$completeOutput) {
            $completeOutput .= $output;
        }, $env);

        if ($exitCode !== 0) {
            throw new \Exception($completeOutput);
        }

        return 0;
    }
}

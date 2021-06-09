<?php

require_once __DIR__ . "/print-line.php";

function handleHelpCommand(array $argv, int $argc, string $outputHelp): bool
{
    if ($argc === 2 && in_array($argv[1], ["-h", "--help", "help"], true)) {
        printLine($outputHelp);
        return true;
    }

    return false;
}

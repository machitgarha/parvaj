<?php

function error(string $message, int $code = 1): int
{
    printLine($message);
    printLine("See --help for more information.");

    return $code;
}

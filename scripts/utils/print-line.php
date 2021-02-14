<?php

function printLine(...$args): void
{
    foreach ($args as $arg) {
        if (is_array($arg)) {
            foreach ($arg as $line) {
                printLine($line);
            }
            return;
        } else {
            echo $arg;
        }
    }
    echo PHP_EOL;
}

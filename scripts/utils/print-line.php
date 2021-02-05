<?php

function printLine(...$args)
{
    foreach ($args as $arg) {
        if (is_array($arg)) {
            printLine(...$arg);
        }
        echo $arg;
    }
    echo PHP_EOL;
}

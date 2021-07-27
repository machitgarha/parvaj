<?php

use MAChitgarha\Parvaj\Command\RunUnitTestCommand;
use MAChitgarha\Parvaj\Command\CreateEntityCommand;

use Symfony\Component\Console\Application;

require __DIR__ . "/../vendor/autoload.php";

$app = new Application(
    'Parvaj',
);
$app->add(new CreateEntityCommand());
$app->add(new RunUnitTestCommand());
$app->run();

<?php

namespace MAChitgarha\Parvaj\Console;

use MAChitgarha\Parvaj\Command\CreateEntityCommand;
use MAChitgarha\Parvaj\Command\RunUnitTestCommand;

class Application extends \Symfony\Component\Console\Application
{
    public const NAME = 'Parvaj';
    public const VERSION = '0.2.0-alpha.1';

    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);

        $this->addAllCommands();
    }

    private function addAllCommands()
    {
        $this->addCommands([
            new CreateEntityCommand(),
            new RunUnitTestCommand(),
        ]);
    }
};

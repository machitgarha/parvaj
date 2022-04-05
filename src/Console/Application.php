<?php

namespace MAChitgarha\Parvaj\Console;

use MAChitgarha\Parvaj\Command\SimulateCommand;

class Application extends \Symfony\Component\Console\Application
{
    public const NAME = "Parvaj";
    public const VERSION = "0.3.3";

    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);

        $this->addAllCommands();
    }

    private function addAllCommands()
    {
        $this->addCommands([
            new SimulateCommand(),
        ]);
    }
};

<?php

namespace MAChitgarha\Parvaj\Console;

use MAChitgarha\Parvaj\Command\SimulateCommand;

class Application extends \Symfony\Component\Console\Application
{
    public const NAME = "Parvaj";
    public const VERSION = "0.4.0-alpha.1";

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

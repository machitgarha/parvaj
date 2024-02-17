<?php

namespace MAChitgarha\Parvaj\Console;

use MAChitgarha\Parvaj\Command\ConfigCommand;
use MAChitgarha\Parvaj\Command\SimulateCommand;

class Application extends \Symfony\Component\Console\Application
{
    public const NAME = "Parvaj";
    public const VERSION = "0.5.2";

    public const ISSUES_PAGE_LINK = "https://github.com/machitgarha/parvaj/issues";

    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);

        $this->addAllCommands();
    }

    private function addAllCommands()
    {
        $this->addCommands([
            new ConfigCommand(),
            new SimulateCommand(),
        ]);
    }
};

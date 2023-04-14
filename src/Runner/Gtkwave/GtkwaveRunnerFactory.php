<?php

namespace MAChitgarha\Parvaj\Runner\Gtkwave;

use MAChitgarha\Parvaj\Config;

use MAChitgarha\Parvaj\Util\ExecutableFinder;

class GtkwaveRunnerFactory
{
    public static function create(ExecutableFinder $executableFinder, Config $config): GtkwaveRunner
    {
        return new GtkwaveRunner($executableFinder, $config);
    }
}

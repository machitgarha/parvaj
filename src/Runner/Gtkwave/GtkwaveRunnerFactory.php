<?php

namespace MAChitgarha\Parvaj\Runner\Gtkwave;

use MAChitgarha\Parvaj\Util\ExecutableFinder;

class GtkwaveRunnerFactory
{
    public static function create(ExecutableFinder $executableFinder): GtkwaveRunner
    {
        return new GtkwaveRunner(
            $executableFinder->find("gtkwave")
        );
    }
}

<?php

namespace MAChitgarha\Parvaj;

use Webmozart\PathUtil\Path;

class UnitTestUnitFilePathGenerator extends AbstractUnitFilePathGenerator
{
    protected static function getOperatingDirectory(): string
    {
        return Path::join('tests', 'unit');
    }
}

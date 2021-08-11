<?php

namespace MAChitgarha\Parvaj;

use Webmozart\PathUtil\Path;

class UnitTestEntityFilePathGenerator extends AbstractEntityFilePathGenerator
{
    protected static function getOperatingDirectory(): string
    {
        return Path::join('tests', 'unit');
    }
}

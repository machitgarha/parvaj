<?php

namespace MAChitgarha\Parvaj\File\PathGenerator;

use Webmozart\PathUtil\Path;

class UnitTestFilePath extends UnitTestFilePath
{
    protected static function getOperatingDirectory(): string
    {
        return Path::join('tests', 'unit');
    }
}

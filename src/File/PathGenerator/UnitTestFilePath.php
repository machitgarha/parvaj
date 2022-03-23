<?php

namespace MAChitgarha\Parvaj\File\PathGenerator;

use Symfony\Component\Filesystem\Path;

class UnitTestFilePath extends AbstractFilePath
{
    protected static function getOperatingDirectory(): string
    {
        return Path::join('tests', 'unit');
    }
}

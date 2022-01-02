<?php

namespace MAChitgarha\Parvaj\File\PathGenerator;

class SourceFilePath extends SourceFilePath
{
    protected static function getOperatingDirectory(): string
    {
        return 'src';
    }
}

<?php

namespace MAChitgarha\Parvaj\File\PathGenerator;

class SourceFilePath extends AbstractFilePath
{
    protected static function getOperatingDirectory(): string
    {
        return 'src';
    }
}

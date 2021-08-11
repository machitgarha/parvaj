<?php

namespace MAChitgarha\Parvaj;

class SourceEntityFilePathGenerator extends AbstractEntityFilePathGenerator
{
    protected static function getOperatingDirectory(): string
    {
        return 'src';
    }
}

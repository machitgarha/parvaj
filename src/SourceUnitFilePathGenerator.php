<?php

namespace MAChitgarha\Parvaj;

class SourceUnitFilePathGenerator extends AbstractUnitFilePathGenerator
{
    protected static function getOperatingDirectory(): string
    {
        return 'src';
    }
}

<?php

namespace MAChitgarha\Parvaj;

class SourceEntityFilePathGenerator extends AbstractEntityFilePathGenerator
{
    protected function getOperatingDirectory(): string
    {
        return 'src';
    }
}

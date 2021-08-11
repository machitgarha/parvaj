<?php

namespace MAChitgarha\Parvaj;

class UnitTestEntityFileContentGenerator extends
    AbstractEntityFileContentGenerator
{
    protected function getTemplateFileName(): string
    {
        return 'unit-test-entity.vhd.twig';
    }
}

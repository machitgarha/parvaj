<?php

namespace MAChitgarha\Parvaj;

class SourceEntityFileContentGenerator extends
    AbstractEntityFileContentGenerator
{
    protected function getTemplateFileName(): string
    {
        return 'source-entity.vhd.twig';
    }
}

<?php

namespace MAChitgarha\Parvaj\File\ContentGenerator;

class UnitTestEntityContent extends AbstractEntityContent
{
    protected function getTemplateFileName(): string
    {
        return 'unit-test-entity.vhd.twig';
    }
}

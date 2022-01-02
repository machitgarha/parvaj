<?php

namespace MAChitgarha\Parvaj\File\ContentGenerator;

class UnitTestEntityContent extends
    UnitTestEntityContent
{
    protected function getTemplateFileName(): string
    {
        return 'unit-test-entity.vhd.twig';
    }
}

<?php

namespace MAChitgarha\Parvaj\File\ContentGenerator;

class SourceEntityContent extends
    SourceEntityContent
{
    protected function getTemplateFileName(): string
    {
        return 'source-entity.vhd.twig';
    }
}

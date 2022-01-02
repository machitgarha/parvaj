<?php

namespace MAChitgarha\Parvaj\File\ContentGenerator;

class SourceEntityContent extends AbstractEntityContent
{
    protected function getTemplateFileName(): string
    {
        return 'source-entity.vhd.twig';
    }
}

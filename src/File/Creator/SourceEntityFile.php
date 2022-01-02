<?php

namespace MAChitgarha\Parvaj\File\Creator;

use MAChitgarha\Parvaj\File\PathGenerator\SourceFilePath;
use MAChitgarha\Parvaj\File\ContentGenerator\SourceEntityContent;

class SourceEntityFile extends AbstractEntityFile
{
    public function __construct(
        SourceFilePath $filePathGenerator,
        SourceEntityContent $contentGenerator,
        string $entityName,
        string $groupName,
        string $architectureName
    ) {
        parent::__construct(
            $filePathGenerator,
            $contentGenerator,
            $entityName,
            $groupName,
            $architectureName,
        );
    }
}

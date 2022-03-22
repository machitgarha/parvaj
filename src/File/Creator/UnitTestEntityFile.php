<?php

namespace MAChitgarha\Parvaj\File\Creator;

use MAChitgarha\Parvaj\File\PathGenerator\UnitTestFilePath;
use MAChitgarha\Parvaj\File\ContentGenerator\UnitTestEntityContent;

class UnitTestEntityFile extends AbstractEntityFile
{
    public function __construct(
        UnitTestFilePath $filePathGenerator,
        UnitTestEntityContent $contentGenerator,
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
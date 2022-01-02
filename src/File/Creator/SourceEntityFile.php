<?php

namespace MAChitgarha\Parvaj\File\Creator;

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

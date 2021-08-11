<?php

namespace MAChitgarha\Parvaj;

class SourceEntityFileCreator extends AbstractEntityFileCreator
{
    public function __construct(
        SourceEntityFilePathGenerator $filePathGenerator,
        SourceEntityFileContentGenerator $contentGenerator,
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

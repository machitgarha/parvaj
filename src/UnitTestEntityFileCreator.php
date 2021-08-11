<?php

namespace MAChitgarha\Parvaj;

class UnitTestEntityFileCreator extends AbstractEntityFileCreator
{
    public function __construct(
        UnitTestUnitFilePathGenerator $filePathGenerator,
        UnitTestEntityFileContentGenerator $contentGenerator,
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
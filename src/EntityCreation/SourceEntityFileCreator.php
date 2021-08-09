<?php

namespace MAChitgarha\Parvaj;

class SourceEntityFileCreator extends AbstractEntityFileCreator
{
    protected const TEMPLATE_FILE_PATH = parent::TEMPLATES_DIR . "/source-entity.vhd";

    protected function generatePath(): string
    {
        return (new SourceEntityFileInfo($this->entityName, $this->groupName))->getPath();
    }

    protected function generateContents(): string
    {
        $file = new \SplFileObject(self::TEMPLATE_FILE_PATH, "r");

        return self::replacePlaceholders($file->fread($file->getSize()), [
            "entity-name" => $this->entityName,
            "architecture-name" => $this->architectureName,
        ]);
    }
}

<?php

namespace MAChitgarha\Parvaj;

use MAChitgarha\Parvaj\UnitTestEntityFileInfo;

class UnitTestEntityFileCreator extends AbstractEntityFileCreator
{
    protected const ERR_FILE_EXISTS = "Unit-test VHDL file already exists.";

    private const TEMPLATE_FILE_PATH = parent::TEMPLATES_DIR . "/unit-test-entity.vhd";

    private string $testEntityName;

    public function __construct(
        string $entityName,
        string $groupName,
        string $architectureName = "structural"
    ) {
        $this->testEntityName = "test_$entityName";

        parent::__construct($entityName, $groupName, $architectureName);

        echo $this->path . PHP_EOL;
    }

    protected function generatePath(): string
    {
        return (new UnitTestEntityFileInfo(
            $this->testEntityName, $this->groupName
        ))->getPath();
    }

    protected function generateContents(): string
    {
        $file = new SplFileObject(self::TEMPLATE_FILE_PATH, "r");

        return self::replacePlaceholders($file->fread($file->getSize()), [
            "entity-name" => $this->testEntityName,
            "architecture-name" => $this->architectureName,
            "component" => $this->getSourceEntityAsComponent(),
            "source-entity-name" => $this->entityName,
        ]);
    }

    private function getSourceEntityAsComponent(): string
    {
        $sourceEntityPath = (new SourceEntityFileCreator(
            $this->entityName,
            $this->groupName,
            $this->architectureName
        ))->generatePath();

        try {
            $sourceEntityFile = new \SplFileObject($sourceEntityPath, "r");
        } catch (\RuntimeException $e) {
            throw new \Exception(
                "Source entity file does not exist ($sourceEntityPath)."
            );
        }

        $contents = $sourceEntityFile->fread($sourceEntityFile->getSize());

        if (!preg_match("/(entity)[\s\S]*(entity;)/i", $contents, $matches)) {
            throw new \Exception(
                "Source file does not contain any entities ($sourceEntityPath)."
            );
        }

        return str_replace("entity", "component", implode(PHP_EOL, array_map(
            fn ($i) => empty($i) ? "" : ("    " . $i),
            explode(PHP_EOL, $matches[0])
        )));
    }
}

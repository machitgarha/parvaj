<?php

abstract class EntityFileCreator
{
    protected const ERR_FILE_EXISTS = "VHDL file already exists.";

    protected string $pathe;
    protected string $contents = "";

    protected string $entityName;
    protected string $groupName;
    protected string $architectureName;

    public function __construct(
        string $entityName,
        string $groupName,
        string $architectureName = "structural"
    ) {
        $this->entityName = $entityName;
        $this->groupName = $groupName;
        $this->architectureName = $architectureName;

        $this->path = $this->generatePath($entityName, $groupName);
        $this->contents = $this->generateContents($entityName, $architectureName);
    }

    abstract public function generatePath(): string;
    abstract public function generateContents(): string;

    protected static function ensureNotExists(string $filePath): void
    {
        if (file_exists($filePath)) {
            throw new \Exception(static::ERR_FILE_EXISTS);
        }
    }

    protected static function createParentDirectories(string $filePath): void
    {
        $dir = dirname($filePath);
        if (!is_dir($dir) && !mkdir(dirname($filePath), 0755, true)) {
            throw new \Exception("Cannot create file's parent directories.");
        }
    }

    protected static function canonicalizeName(string $filename): string
    {
        return str_replace("_", "-", $filename);
    }

    protected static function replacePlaceholders(
        string $templateString,
        array $replacementMappings
    ): string {
        return str_replace(
            array_map(fn ($i) => "<$i>", array_keys($replacementMappings)),
            array_values($replacementMappings),
            $templateString
        );
    }

    public function write(): void
    {
        self::ensureNotExists($this->path);
        self::createParentDirectories($this->path);

        $file = new SplFileObject($this->path, "w");
        $file->fwrite($this->contents);
    }
}

class SourceEntityFileCreator extends EntityFileCreator
{
    private const BASE_DIRECTORY = __DIR__ . "/../../src";
    private const TEMPLATE_FILE_PATH = __DIR__ . "/../templates/source-entity.vhd";

    public function generatePath(): string
    {
        return self::BASE_DIRECTORY . "/{$this->groupName}/" .
            self::canonicalizeName($this->entityName) . ".vhd";
    }

    public function generateContents(): string
    {
        $file = new SplFileObject(self::TEMPLATE_FILE_PATH, "r");

        return self::replacePlaceholders($file->fread($file->getSize()), [
            "entity-name" => $this->entityName,
            "architecture-name" => $this->architectureName,
        ]);
    }
}

class UnitTestEntityFileCreator extends EntityFileCreator
{
    protected const ERR_FILE_EXISTS = "Unit-test VHDL file already exists.";

    private const BASE_DIRECTORY = __DIR__ . "/../../tests/unit";
    private const TEMPLATE_FILE_PATH = __DIR__ . "/../templates/unit-test-entity.vhd";

    public function generatePath(): string
    {
        return self::BASE_DIRECTORY . "/{$this->groupName}/" .
            self::canonicalizeName("test_{$this->entityName}") . ".vhd";
    }

    public function generateContents(): string
    {
        $file = new SplFileObject(self::TEMPLATE_FILE_PATH, "r");

        return self::replacePlaceholders($file->fread($file->getSize()), [
            "entity-name" => "test_{$this->entityName}",
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
            $sourceEntityFile = new SplFileObject($sourceEntityPath, "r");
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

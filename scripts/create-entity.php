#!/usr/bin/env php
<?php

require __DIR__ . "/utils/print-line.php";

$outputHelp = <<<OUTPUT

Usage:
    {$argv[0]} <entity-type> <entity-name> <group-name> [<architecture-name>]

Description:
    Creates a VHDL file, containing the base structure of an entity. <entity-type>
    determines the purpose of the entity and its file location, which is one of the
    followings:

    source:
        A VHDL source file, including one entity providing the main functionality of the
        program. It will be created in src/<group-name> directory. <entity-name> is used
        for entity's name, and <architecture-name> for its architecture name.

    unit-test:
        A VHDL file including an entity for (unit-)testing a particular source entity.
        It will be located in tests/unit/<group-name> directory. The name of the resulting
        entity will be test_<entity-name>, where <entity-name> is the name of the source
        entity to be tested. Its architecture name will also be <architecture-name>.

    In both cases, the filename will be the entity name with underscores replaced with
    dashes, with .vhd extension.

OUTPUT;

function error(string $message, int $code = 1): int
{
    printLine($message);
    printLine("See --help for more information.");

    return $code;
}

abstract class EntityFileCreator
{
    protected string $path;
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
            throw new \Exception("VHDL file already exists.");
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
    private const BASE_DIRECTORY = __DIR__ . "/../src";
    private const TEMPLATE_FILE_PATH = __DIR__ . "/templates/source-entity.vhd";

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
    private const BASE_DIRECTORY = __DIR__ . "/../tests/unit";
    private const TEMPLATE_FILE_PATH = __DIR__ . "/templates/unit-test-entity.vhd";

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

        if (!preg_match("/((entity)[^,]*(entity;))/i", $contents, $matches)) {
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


function prepareShellArguments(array &$argv): void
{
    // <architecture-name> default value
    $argv[4] ??= "structural";
}

$argc--;

if ($argc === 1 && in_array($argv[1], ["-h", "--help", "help"], true)) {
    printLine($outputHelp);
    return 0;
}

if ($argc < 3) {
    return error("Too few arguments.");
}

prepareShellArguments($argv);
[, $entityType, $entityName, $groupName, $architectureName] = $argv;

if ($entityType === "source") {
    $className = SourceEntityFileCreator::class;
} elseif ($entityType === "unit-test") {
    $className = UnitTestEntityFileCreator::class;
} else {
    return error("Unknown entity type '$entityType'.");
}

try {
    (new $className($entityName, $groupName, $architectureName))->write();
    return 0;
} catch (\Throwable $e) {
    return error($e->getMessage(), $e->getCode());
}

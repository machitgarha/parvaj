#!/usr/bin/env php
<?php

require __DIR__ . "/utils/print-line.php";

const SRC_DIR = __DIR__ . "/../src";

const OUTPUT_TOO_FEW_ARGUMENTS = <<<OUTPUT
Too few arguments.
See --help for more information.
OUTPUT;

$outputHelp = <<<OUTPUT

Usage:
    {$argv[0]} <entity-name> <group-name> [<architecture-name>]

Description:
    Creates a VHDL entity source file, consisting of bare definitions of the entity and
    its architecture. The file will be created in src/<group-name> directory, and its
    name will be <entity-name> replacing underscores with dashes.

OUTPUT;

class DestinationFileGenerator
{
    private const BARE_ENTITY_VHDL_FILE_PATH = __DIR__ . "/data/bare-entity.vhd";

    private string $filename;
    private string $contents;

    public function __construct(
        string $entityName,
        string $groupName,
        string $architectureName
    ) {
        $this->generateName($entityName, $groupName);
        $this->generateContents($entityName, $architectureName);
    }

    private function generateName(
        string $entityName,
        string $groupName
    ): void {
        $this->filename = SRC_DIR . "/$groupName/" . str_replace("_", "-", $entityName) .
            ".vhd";
    }

    private function generateContents(
        string $entityName,
        string $architectureName
    ): void {
        $file = new SplFileObject(self::BARE_ENTITY_VHDL_FILE_PATH, "r");

        $this->contents = str_replace(
            ["<entity-name>", "<architecture-name>"],
            [$entityName, $architectureName],
            $file->fread($file->getSize())
        );
    }

    public function write(): void
    {
        $file = new SplFileObject($this->filename, "w");
        $file->fwrite($this->contents);
    }
}

function prepareShellArguments(array &$args): void
{
    // <architecture-name> default value
    $args[3] ??= "structural";
}

$argc--;

if ($argc === 1 && in_array($argv[1], ["-h", "--help", "help"], true)) {
    printLine($outputHelp);
    return 0;
}

if ($argc < 2) {
    printLine(OUTPUT_TOO_FEW_ARGUMENTS);
    return 1;
}

prepareShellArguments($argv);
[, $entityName, $groupName, $architectureName] = $argv;

(new DestinationFileGenerator($entityName, $groupName, $architectureName))->write();
return 0;

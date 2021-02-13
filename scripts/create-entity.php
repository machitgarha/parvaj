#!/usr/bin/env php
<?php

require_once __DIR__ . "/utils/print-line.php";
require_once __DIR__ . "/utils/error.php";
require_once __DIR__ . "/utils/handle-help-command.php";
require_once __DIR__ . "/utils/entity.php";

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

function prepareShellArguments(array &$argv): void
{
    // <architecture-name> default value
    $argv[4] ??= "structural";
}

if (handleHelpCommand($argv, $argc, $outputHelp)) {
    exit(0);
}

$argc--;

if ($argc < 3) {
    exit(error("Too few arguments."));
}

prepareShellArguments($argv);
[, $entityType, $entityName, $groupName, $architectureName] = $argv;

if ($entityType === "source") {
    $className = SourceEntityFileCreator::class;
} elseif ($entityType === "unit-test") {
    $className = UnitTestEntityFileCreator::class;
} else {
    exit(error("Unknown entity type '$entityType'."));
}

try {
    (new $className($entityName, $groupName, $architectureName))->write();
    exit(0);
} catch (\Throwable $e) {
    exit(error($e->getMessage(), $e->getCode()));
}

#!/usr/bin/env php
<?php

require_once __DIR__ . "/vendor/autoload.php";

require_once __DIR__ . "/utils/print-line.php";
require_once __DIR__ . "/utils/error.php";
require_once __DIR__ . "/utils/handle-help-command.php";
require_once __DIR__ . "/utils/entity.php";

use Symfony\Component\Process\Process;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

$outputHelp = <<<OUTPUT

Usage:
    {$argv[0]} <test-entity-name> [<workdir>] [<simulation-options>]

Description:
    Analyzes all needed entities (including source files) by resolving all dependencies,
    elaborates and runs the particular unit-test, and saves the waveform result into a
    standard file, with the help of GHDL. The unit-test is selected based on
    <entity-name>, which is the source entity name to be unit-tested.

    <workdir> is the value of --workdir option passed to the GHDL command. If it
    is empty, it will be omitted.

    <simulation-options> is also passed to GHDL when running the test, known as simulation
    options. Note that, it must not include --wave option, as it is generated
    automatically. An example could be: --stop-time=3ns.

OUTPUT;

const COMPONENT_FINDER_REGEX = "/component\s+([a-z0-9_]+)/i";
const PACKAGE_FINDER_REGEX = "/use\s+work.(\w+).\w+;/i";
const WAVEFORM_FILE_EXTENSION = "ghw";

function getEntityDependentUnitRegexly(string $entityFileContents, string $regex): array
{
    if (preg_match_all($regex, $entityFileContents, $matches)) {
        return $matches[1];
    } else {
        return [];
    }
}

function getEntityDependentComponents(string $entityFileContents): array
{
    return getEntityDependentUnitRegexly($entityFileContents, COMPONENT_FINDER_REGEX);
}

function getEntityDependentPackages(string $entityFileContents): array
{
    return getEntityDependentUnitRegexly($entityFileContents, PACKAGE_FINDER_REGEX);
}

function getEntityDependencies(string $entityFileContents): array
{
    return [
        ...getEntityDependentComponents($entityFileContents),
        ...getEntityDependentPackages($entityFileContents),
    ];
}

function getAllDependenciesPath(string $entityName, bool $isSourceFile = true): array
{
    $entityFileInfo = $isSourceFile ?
        new SourceEntityFileInfo($entityName) :
        new UnitTestEntityFileInfo($entityName);

    // If the path could not be found, an exception is thrown
    $dependenciesPath = [$entityFileInfo->findPath()->getPath()];

    $file = new SplFileObject($dependenciesPath[0], "r");
    $contents = $file->fread($file->getSize());

    foreach (getEntityDependencies($contents) as $dependecyEntityName) {
        $dependenciesPath = [
            ...getAllDependenciesPath($dependecyEntityName),
            ...$dependenciesPath,
        ];
    }

    return $dependenciesPath;
}

function getWaveformFilePath(string $testEntityFilePath)
{
    return dirname($testEntityFilePath) . "/" . str_replace(
        EntityFileInfo::VHDL_EXTENSION,
        WAVEFORM_FILE_EXTENSION,
        basename($testEntityFilePath)
    );
}

function runProcess(array $shellArgs): void
{
    // Never end a process, until the user kills it himself
    $process = new Process($shellArgs, null, null, null, null);

    if ($process->run() !== 0) {
        throw new \Exception(
            !empty($process->getErrorOutput()) ?
            $process->getErrorOutput() :
            $process->getOutput()
        );
    }
}

function findNecessaryCommands(): array
{
    $executableFinder = new ExecutableFinder();

    foreach($paths = [
        "ghdl" => $executableFinder->find("ghdl"),
        "gtkwave" => $executableFinder->find("gtkwave"),
    ] as $item => $exec) {
        if ($exec === null) {
            throw new \Exception("Could not find '$item' executable.");
        }
    }

    return $paths;
}

function analyzeEntityFiles(
    string $ghdlExec,
    array $entityFilePaths,
    string $workdir
): void {
    // TODO: Allow the client to choose VHDL version
    runProcess([$ghdlExec, "-a", "--workdir=$workdir", ...$entityFilePaths]);
}

function elabRun(
    string $ghdlExec,
    string $testEntityName,
    string $outputWaveformFilePath,
    string $workdir,
    string $simulationOptions
): void {
    $simulationOptionsArr =
        empty($simulationOptions) ? [] : explode(" ", $simulationOptions);

    runProcess([
        $ghdlExec, "--elab-run", "--workdir=$workdir", "-o", "$workdir/test-bench",
        "$testEntityName", "--wave=$outputWaveformFilePath", ...$simulationOptionsArr,
    ]);
}

function openGtkWave(string $gtkwaveExec, string $waveformFilePath)
{
    runProcess([$gtkwaveExec, $waveformFilePath]);
}

function prepareShellArguments(array &$argv)
{
    // Default value for <workdir>
    $argv[2] ??= ".";

    // Default value for <simulation-options>
    $argv[3] ??= "";
}

if (handleHelpCommand($argv, $argc, $outputHelp)) {
    exit(0);
}

$argc--;

if ($argc === 0) {
    exit(error("Too few arguments."));
}

prepareShellArguments($argv);

[, $testEntityName, $workdir, $simulationOptions] = $argv;

$entityFilePaths = getAllDependenciesPath($testEntityName, false);

try {
    ["ghdl" => $ghdlExec, "gtkwave" => $gtkwaveExec] = findNecessaryCommands();

    if (!is_dir($workdir) && !mkdir($workdir)) {
        throw new \Exception("Cannot create '$workdir' directory.");
    }

    printLine("Analyzing files...");
    analyzeEntityFiles($ghdlExec, $entityFilePaths, $workdir);

    $waveformFilePath = getWaveformFilePath(
        (new UnitTestEntityFileInfo($testEntityName))->findPath()->getPath()
    );

    printLine("Elab-running the test...");
    elabRun($ghdlExec, $testEntityName, $waveformFilePath, $workdir, $simulationOptions);

    printLine("Opening the results in GtkWave...");
    openGtkWave($gtkwaveExec, $waveformFilePath);

    exit(0);
} catch (\Exception $e) {
    printLine([
        "",
        "Error:",
        $e->getMessage(),
    ]);
    exit(1);
}

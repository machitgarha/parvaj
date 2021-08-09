<?php

namespace MAChitgarha\Parvaj\Command;

use MAChitgarha\Parvaj\SourceEntityFileInfo;
use MAChitgarha\Parvaj\AbstractEntityFileInfo;
use MAChitgarha\Parvaj\UnitTestEntityFileInfo;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

class RunUnitTestCommand extends Command
{
    protected const NAME = 'run-unit-test';
    protected const DESCRIPTION = <<<'DESCRIPTION'
        Prepares a unit-test entity and runs it.
        DESCRIPTION;
    // TODO: Add an example
    protected const HELP = <<<'HELP'
        Analyzes all needed entities (including source files) by resolving all
        dependencies, elaborates and runs the particular unit-test, and saves
        the waveform result into a file, with the help of GHDL. At last, it
        displays the waveform in a GtkWave window.
        HELP;

    protected const ARG_TEST_ENTITY_NAME_NAME = 'test-entity-name';
    protected const ARG_TEST_ENTITY_NAME_DESCRIPTION =
        'The name of the unit-test entity.';

    protected const OPT_WORKDIR_NAME = 'workdir';
    protected const OPT_WORKDIR_DESCRIPTION =
        'Where temporary files live is the working directory (e.g. object ' . 'files). You can consider it the value of --workdir option passed to ' .
        ' GHDL.';
    protected const OPT_WORKDIR_DEFAULT = 'build/';

    protected const OPT_SIMULATION_OPTIONS_NAME = 'simulation-options';
    protected const OPT_SIMULATION_OPTIONS_DESCRIPTION =
        'Simulation options passed to GHDL when running the test. It must ' .
        'not include the --wave option, as it is generated automatically. An ' .
        ' example could be: --stop-time=3ns.';

    private const COMPONENT_FINDER_REGEX = "/component\s+([a-z0-9_]+)/i";
    private const PACKAGE_FINDER_REGEX = "/use\s+work.(\w+).\w+;/i";
    private const WAVEFORM_FILE_EXTENSION = "ghw";

    protected function configure()
    {
        // Metadata
        $this
            ->setName(static::NAME)
            ->setDescription(static::DESCRIPTION)
            ->setHelp(static::HELP)
        ;

        // Arguments and options
        $this
            ->addArgument(
                static::ARG_TEST_ENTITY_NAME_NAME,
                InputArgument::REQUIRED,
                static::ARG_TEST_ENTITY_NAME_DESCRIPTION,
            )
            ->addOption(
                static::OPT_WORKDIR_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                static::OPT_WORKDIR_DESCRIPTION,
                static::OPT_WORKDIR_DEFAULT,
            )
            ->addOption(
                static::OPT_SIMULATION_OPTIONS_NAME,
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                static::OPT_SIMULATION_OPTIONS_DESCRIPTION,
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        [$testEntityName, $workdir, $simulationOptions] = [
            $input->getArgument(static::ARG_TEST_ENTITY_NAME_NAME),
            $input->getOption(static::OPT_WORKDIR_NAME),
            $input->getOption(static::OPT_SIMULATION_OPTIONS_NAME),
        ];

        $entityFilePaths = getAllDependenciesPath($testEntityName, false);

        try {
            ["ghdl" => $ghdlExec, "gtkwave" => $gtkwaveExec] = findNecessaryCommands();

            if (!\is_dir($workdir) && !\mkdir($workdir)) {
                throw new \Exception("Cannot create '$workdir' directory.");
            }

            $output->writeln("Analyzing files...");
            analyzeEntityFiles($ghdlExec, $entityFilePaths, $workdir);

            $waveformFilePath = getWaveformFilePath(
                (new UnitTestEntityFileInfo($testEntityName))->findPath()->getPath()
            );

            $output->writeln("Elab-running the test...");
            elabRun($ghdlExec, $testEntityName, $waveformFilePath, $workdir, $simulationOptions);

            $output->writeln("Opening the results in GtkWave...");
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
    }

    protected function getEntityDependentUnitRegexly(
        string $entityFileContents,
        string $regex
    ): array {
        if (preg_match_all($regex, $entityFileContents, $matches)) {
            return $matches[1];
        } else {
            return [];
        }
    }

    protected function getEntityDependentComponents(string $entityFileContents): array
    {
        return getEntityDependentUnitRegexly($entityFileContents, self::COMPONENT_FINDER_REGEX);
    }

    protected function getEntityDependentPackages(string $entityFileContents): array
    {
        return getEntityDependentUnitRegexly($entityFileContents, self::PACKAGE_FINDER_REGEX);
    }

    protected function getEntityDependencies(string $entityFileContents): array
    {
        return [
            ...getEntityDependentComponents($entityFileContents),
            ...getEntityDependentPackages($entityFileContents),
        ];
    }

    protected function getAllDependenciesPath(string $entityName, bool $isSourceFile = true): array
    {
        $entityFileInfo = $isSourceFile ?
            new SourceEntityFileInfo($entityName) :
            new UnitTestEntityFileInfo($entityName);

        // If the path could not be found, an exception is thrown
        $dependenciesPath = [$entityFileInfo->findPath()->getPath()];

        $file = new \SplFileObject($dependenciesPath[0], "r");
        $contents = $file->fread($file->getSize());

        foreach (getEntityDependencies($contents) as $dependecyEntityName) {
            $dependenciesPath = [
                ...getAllDependenciesPath($dependecyEntityName),
                ...$dependenciesPath,
            ];
        }

        return $dependenciesPath;
    }

    protected function getWaveformFilePath(string $testEntityFilePath)
    {
        return \dirname($testEntityFilePath) . "/" . \str_replace(
            AbstractEntityFileInfo::VHDL_EXTENSION,
            self::WAVEFORM_FILE_EXTENSION,
            \basename($testEntityFilePath)
        );
    }

    protected function runProcess(array $shellArgs): void
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

    protected function findNecessaryCommands(): array
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

    protected function analyzeEntityFiles(
        string $ghdlExec,
        array $entityFilePaths,
        string $workdir
    ): void {
        // TODO: Allow the client to choose VHDL version
        runProcess([$ghdlExec, "-a", "--workdir=$workdir", ...$entityFilePaths]);
    }

    protected function elabRun(
        string $ghdlExec,
        string $testEntityName,
        string $outputWaveformFilePath,
        string $workdir,
        string $simulationOptions
    ): void {
        $simulationOptionsArr =
            empty($simulationOptions) ? [] : \explode(" ", $simulationOptions);

        runProcess([
            $ghdlExec, "--elab-run", "--workdir=$workdir", "-o", "$workdir/test-bench",
            "$testEntityName", "--wave=$outputWaveformFilePath", ...$simulationOptionsArr,
        ]);
    }

    protected function openGtkWave(string $gtkwaveExec, string $waveformFilePath)
    {
        runProcess([$gtkwaveExec, $waveformFilePath]);
    }
}

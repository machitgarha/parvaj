<?php

namespace MAChitgarha\Parvaj\Command;

use MAChitgarha\Parvaj\DependencyResolver;
use MAChitgarha\Parvaj\UnitTestUnitFilePathGenerator;

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
        'GHDL.';
    protected const OPT_WORKDIR_DEFAULT = 'build/';

    protected const OPT_SIMULATION_OPTIONS_NAME = 'simulation-options';
    protected const OPT_SIMULATION_OPTIONS_DESCRIPTION =
        'Simulation options passed to GHDL when running the test. It must ' .
        'not include the --wave option, as it is generated automatically. An ' .
        'example could be: --stop-time=3ns.';

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

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $testEntityName = $input->getArgument(
            static::ARG_TEST_ENTITY_NAME_NAME
        );
        $workdir = $input->getOption(
            static::OPT_WORKDIR_NAME
        );
        $simulationOptions = $input->getOption(
            static::OPT_SIMULATION_OPTIONS_NAME
        );

        $dependencyResolver = new DependencyResolver(
            $unitTestEntityPath = UnitTestUnitFilePathGenerator::locate(
                $testEntityName
            )
        );

        $unitFilePaths = \iterator_to_array(
            $$dependencyResolver->resolve()
        );

        ["ghdl" => $ghdlExec, "gtkwave" => $gtkwaveExec] =
            self::findNecessaryCommands();

        self::makeWorkdir($workdir);

        $output->writeln("Analyzing files...");
        self::analyzeEntityFiles($ghdlExec, $unitFilePaths, $workdir);

        $waveformFilePath = self::getWaveformFilePath($unitTestEntityPath);

        $output->writeln("Elab-running the test...");
        self::elabRun(
            $ghdlExec,
            $testEntityName,
            $waveformFilePath,
            $workdir,
            $simulationOptions
        );

        $output->writeln("Opening the results in GtkWave...");
        openGtkWave($gtkwaveExec, $waveformFilePath);

        return 0;
    }

    private static function getWaveformFilePath(
        string $testEntityFilePath
    ): string {
        return \dirname($testEntityFilePath) . "/" . \str_replace(
            AbstractEntityFileInfo::VHDL_EXTENSION,
            self::WAVEFORM_FILE_EXTENSION,
            \basename($testEntityFilePath)
        );
    }

    private static function runProcess(array $shellArgs): void
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

    private static function findNecessaryCommands(): array
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

    private static function makeWorkdir(string $workdirPath): void
    {
        if (!\is_dir($workdirPath) && !@\mkdir($workdirPath, 0755, true)) {
            throw new \Exception("Unable to create '$workdirPath' directory.");
        }
    }

    private static function function analyzeEntityFiles(
        string $ghdlExec,
        array $unitFilePaths,
        string $workdir
    ): void {
        // TODO: Allow the client to choose VHDL version
        runProcess([$ghdlExec, "-a", "--workdir=$workdir", ...$unitFilePaths]);
    }

    private static function elabRun(
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

    private static function openGtkWave(
        string $gtkwaveExec,
        string $waveformFilePath
    ): void {
        runProcess([$gtkwaveExec, $waveformFilePath]);
    }
}

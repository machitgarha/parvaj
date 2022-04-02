<?php

namespace MAChitgarha\Parvaj\Command;

use MAChitgarha\Parvaj\PathFinder;
use MAChitgarha\Parvaj\DependencyResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Filesystem\Path;

class SimulateCommand extends Command
{
    protected const NAME = "simulate";
    protected const DESCRIPTION = <<<DESCRIPTION
        Simulates a test-bench.
        DESCRIPTION;
    // TODO: Add an example
    protected const HELP = <<<HELP
        Simulates a test-bench (i.e. unit-test) entity.

        Analyzes all required files by resolving all dependencies, elaborates
        and runs it, makes a waveform file (all with the help of GHDL), and at
        last, displays the waveform visually via GTKWave.
        HELP;

    protected const ARG_TEST_ENTITY_NAME_NAME = "test-bench";
    protected const ARG_TEST_ENTITY_NAME_DESCRIPTION =
        "Name of the simulating test-bench.";

    protected const OPT_WORKDIR_NAME = "workdir";
    protected const OPT_WORKDIR_DESCRIPTION =
        "The work directory. Same as GHDL --workdir option. It is where " .
        "temporaries like object files are placed.";
    protected const OPT_WORKDIR_DEFAULT = "build/";

    protected const OPT_WAVEFORM_NAME = "waveform";
    protected const OPT_WAVEFORM_DESCRIPTION =
        "The waveform file format. Either ghw and vcd. Case-insensitive.";
    protected const OPT_WAVEFORM_DEFAULT = "vcd";

    protected const OPT_NO_O_NAME = "no-o";
    protected const OPT_NO_O_DESCRIPTION =
        "Do not use -o option. It pollutes the project directory, but useful " .
        "for older GHDL versions where the option is unavailable.";

    protected const OPT_SIMULATION_OPTION_NAME = "option";
    protected const OPT_SIMULATION_OPTION_DESCRIPTION =
        "Simulation options passed to GHDL at the run step, without the " .
        "leading dashes. Options used by Parvaj itself must not be used, " .
        "including 'workdir', 'vcd', 'vcd' and 'o'. There must be exactly ".
        "one option per --option. The format of the key is 'key=value'. For " .
        "example, `-o stop-time=3ns` is valid.";
    protected const OPT_SIMULATION_OPTION_SHORTCUT = "o";

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
                static::OPT_WAVEFORM_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                static::OPT_WAVEFORM_DESCRIPTION,
                static::OPT_WAVEFORM_DEFAULT,
            )
            ->addOption(
                static::OPT_NO_O_NAME,
                null,
                InputOption::VALUE_NONE,
                static::OPT_NO_O_DESCRIPTION,
            )
            ->addOption(
                static::OPT_SIMULATION_OPTION_NAME,
                static::OPT_SIMULATION_OPTION_SHORTCUT,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                static::OPT_SIMULATION_OPTION_DESCRIPTION,
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
        $waveformType = $input->getOption(
            static::OPT_WAVEFORM_NAME
        );
        $noO = $input->getOption(
            static::OPT_NO_O_NAME
        );
        $optionsArray = $input->getOption(
            static::OPT_SIMULATION_OPTION_NAME
        );

        $pathFinder = new PathFinder(".");
        $unitFilePaths = (new DependencyResolver($pathFinder))
            ->resolve($testEntityName);

        ["ghdl" => $ghdlExec, "gtkwave" => $gtkwaveExec] =
            self::findNecessaryCommands();

        self::makeWorkdir($workdir);

        $output->writeln("Analyzing files...");
        self::analyzeEntityFiles($ghdlExec, $unitFilePaths, $workdir);

        $waveformFilePath = self::generateWaveformFilePath(
            $workdir,
            $testEntityName,
            $waveformType,
        );

        $output->writeln("Elab-running the test...");
        self::elabRun(
            $ghdlExec,
            $testEntityName,
            $waveformFilePath,
            $workdir,
            $waveformType,
            $noO,
            self::normalizeSimulationOptions($optionsArray),
        );

        $output->writeln("Opening the results in GtkWave...");
        self::openGtkWave($gtkwaveExec, $waveformFilePath);

        return 0;
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

    private static function analyzeEntityFiles(
        string $ghdlExec,
        array $unitFilePaths,
        string $workdir
    ): void {
        // TODO: Allow the client to choose VHDL version
        self::runProcess([
            $ghdlExec, "-a", "--workdir=$workdir", ...$unitFilePaths
        ]);
    }

    private static function generateWaveformFilePath(
        string $workdir,
        string $testEntityName,
        string $waveformType
    ): string {
        return Path::join($workdir, "$testEntityName.$waveformType");
    }

    private static function normalizeSimulationOptions(array $options): array
    {
        return \array_map(
            fn (string $option) => (
                // Append one or two dashes based on option length
                \strlen(explode('=', $option)[0]) === 1 ? '-' : '--'
            ) . $option,
            $options
        );
    }

    private static function elabRun(
        string $ghdlExec,
        string $testEntityName,
        string $outputWaveformFilePath,
        string $workdir,
        string $waveformType,
        bool $noO,
        array $options
    ): void {
        $waveformOption = match (\strtolower($waveformType)) {
            "vcd" => "--vcd=$outputWaveformFilePath",
            "ghw" => "--wave=$outputWaveformFilePath",

            default => throw new \RuntimeException(
                "Invalid waveform type '$waveformType'"
            ),
        };

        $oOption = ["-o", Path::join($workdir, $testEntityName)];
        if ($noO) {
            $oOption = [];
        }

        self::runProcess([
            $ghdlExec, "--elab-run", "--workdir=$workdir", ...$oOption,
            $testEntityName, $waveformOption, ...$options,
        ]);
    }

    private static function openGtkWave(
        string $gtkwaveExec,
        string $waveformFilePath
    ): void {
        self::runProcess([$gtkwaveExec, $waveformFilePath]);
    }
}

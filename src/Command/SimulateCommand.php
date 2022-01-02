<?php

namespace MAChitgarha\Parvaj\Command;

use MAChitgarha\Parvaj\DependencyResolver;
use MAChitgarha\Parvaj\File\PathGenerator\AbstractFilePath;
use MAChitgarha\Parvaj\File\PathGenerator\UnitTestFilePath;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ExecutableFinder;

use Webmozart\PathUtil\Path;

class SimulateCommand extends Command
{
    protected const NAME = 'simulate';
    protected const DESCRIPTION = <<<'DESCRIPTION'
        Simulates a unit-test entity.
        DESCRIPTION;
    // TODO: Add an example
    protected const HELP = <<<'HELP'
        Runs a simulation for a particular unit-test entity.

        Analyzes all needed source unit files by resolving all dependencies,
        elaborates and runs the particular unit-test, and dumps the waveform
        into a file, all with the help of GHDL. At last, it displays the
        waveform visually in a GtkWave window.
        HELP;

    protected const ARG_TEST_ENTITY_NAME_NAME = 'test-entity-name';
    protected const ARG_TEST_ENTITY_NAME_DESCRIPTION =
        'The name of the unit-test entity.';

    protected const OPT_WORKDIR_NAME = 'workdir';
    protected const OPT_WORKDIR_DESCRIPTION =
        'Where temporary files live is the working directory (e.g. object ' . 'files). You can consider it the value of --workdir option passed to ' .
        'GHDL.';
    protected const OPT_WORKDIR_DEFAULT = 'build/';

    protected const OPT_WAVEFORM_NAME = 'waveform';
    protected const OPT_WAVEFORM_DESCRIPTION =
        'Which waveform format to be used for the output files. Possible ' .
        'values are ghw and vcd. Case-sensitive, must be all lowercased.';
    protected const OPT_WAVEFORM_DEFAULT = 'ghw';

    protected const OPT_NO_O_NAME = 'no-o';
    protected const OPT_NO_O_DESCRIPTION =
        'Do not use -o option. This pollutes the project directory, but it ' .
        'useful in the case of GHDL not detecting the option.';

    protected const OPT_OPTION_NAME = 'option';
    protected const OPT_OPTION_DESCRIPTION =
        'Simulation options passed to GHDL when running the test. Some ' . 'options must not be used, or you might get an error during the ' .
        'process, including --wave, --workdir and -o. It may make seems too ' .
        'verbose, but for now, there must be exactly one per given option, ' .
        'or things should not work correctly. An example could be: ' .
        '--stop-time=3ns.';

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
                static::OPT_OPTION_NAME,
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                static::OPT_OPTION_DESCRIPTION,
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
            static::OPT_OPTION_NAME
        );

        $dependencyResolver = new DependencyResolver(
            $unitTestEntityPath = UnitTestFilePath::locate(
                $testEntityName
            )
        );

        $unitFilePaths = \array_unique(\iterator_to_array(
            $dependencyResolver->resolve(), false
        ));

        ["ghdl" => $ghdlExec, "gtkwave" => $gtkwaveExec] =
            self::findNecessaryCommands();

        self::makeWorkdir($workdir);

        $output->writeln("Analyzing files...");
        self::analyzeEntityFiles($ghdlExec, $unitFilePaths, $workdir);

        $waveformFilePath = self::generateWaveformFilePath(
            $unitTestEntityPath,
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
            $optionsArray,
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
        string $testEntityFilePath,
        string $waveformType
    ): string {
        return \str_replace(
            AbstractFilePath::VHDL_EXTENSION,
            // TODO: Improve the decision, perhaps with a function?
            $waveformType,
            $testEntityFilePath,
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
        if ($waveformType === 'ghw') {
            $waveformOption = ["--wave=$outputWaveformFilePath"];
        } elseif ($waveformType === 'vcd') {
            $waveformOption = ["--vcd=$outputWaveformFilePath"];
        } else {
            throw new \RuntimeException(
                "Invalid waveform given '$waveformType'"
            );
        }

        $oOption = ['-o', Path::join($workdir, $testEntityName)];
        if ($noO) {
            $oOption = [];
        }

        self::runProcess([
            $ghdlExec, "--elab-run", "--workdir=$workdir", ...$oOption,
            "$testEntityName", ...$waveformOption, ...$options,
        ]);
    }

    private static function openGtkWave(
        string $gtkwaveExec,
        string $waveformFilePath
    ): void {
        self::runProcess([$gtkwaveExec, $waveformFilePath]);
    }
}

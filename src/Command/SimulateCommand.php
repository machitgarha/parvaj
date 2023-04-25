<?php

namespace MAChitgarha\Parvaj\Command;

use MAChitgarha\Component\Pusheh;

use MAChitgarha\Parvaj\Config;

use MAChitgarha\Parvaj\PathFinder;
use MAChitgarha\Parvaj\DependencyResolver;
use MAChitgarha\Parvaj\Runner\Ghdl\GhdlRunner;
use MAChitgarha\Parvaj\Runner\Ghdl\ElabRunUserOptions;
use MAChitgarha\Parvaj\Runner\Ghdl\GhdlRunnerFactory;
use MAChitgarha\Parvaj\Runner\Gtkwave\GtkwaveRunnerFactory;
use MAChitgarha\Parvaj\Util\ExecutableFinder;

use Symfony\Component\Console\Command\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        "The waveform file format. Either ghw or vcd. Case-insensitive.";
    protected const OPT_WAVEFORM_DEFAULT = "vcd";

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
                static::OPT_SIMULATION_OPTION_NAME,
                static::OPT_SIMULATION_OPTION_SHORTCUT,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                static::OPT_SIMULATION_OPTION_DESCRIPTION,
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $testEntityName = $input->getArgument(
            static::ARG_TEST_ENTITY_NAME_NAME
        );
        $workdir = $input->getOption(
            static::OPT_WORKDIR_NAME
        );
        $waveformType = $input->getOption(
            static::OPT_WAVEFORM_NAME
        );
        $simulationOptions = $input->getOption(
            static::OPT_SIMULATION_OPTION_NAME
        );

        $pathFinder = new PathFinder(".");
        $unitFilePaths = (new DependencyResolver($pathFinder))
            ->resolve($testEntityName);

        $executableFinder = new ExecutableFinder();
        $config = $this->buildConfig($input, $output, $executableFinder);

        $ghdlRunner = GhdlRunnerFactory::create($executableFinder, $config);
        $gtkwaveRunner = GtkwaveRunnerFactory::create($executableFinder, $config);

        Pusheh::createDirRecursive($workdir);

        $output->writeln("Analyzing files...");
        $ghdlRunner->analyze($unitFilePaths);

        $output->writeln("Elab-running the test...");
        $waveformFilePath = $ghdlRunner->elabRun(
            $testEntityName,
            new ElabRunUserOptions($waveformType, $simulationOptions)
        );

        $output->writeln("Opening the results in GtkWave...");
        $gtkwaveRunner->open($waveformFilePath, $waveformType);

        return 0;
    }

    private function buildConfig(
        InputInterface $input,
        OutputInterface $output,
        ExecutableFinder $executableFinder
    ): Config {
        $config = new Config();

        if (!$config->has(Config::KEY_GHDL_VERSION)) {
            $output->writeln("GHDL version not set, auto-detecting...");

            $version = GhdlRunner::detectVersion($executableFinder);
            $output->writeln("Detected GHDL version: {$version->getFull()}");

            $config->setGhdlVersion($version->getType());
            $output->writeln("");
        }

        return $config;
    }
}

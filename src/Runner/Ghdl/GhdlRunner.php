<?php

namespace MAChitgarha\Parvaj\Runner\Ghdl;

use OutOfBoundsException;

use MAChitgarha\Parvaj\Console\Application;

use MAChitgarha\Parvaj\Runner\OptionBuilder;

use MAChitgarha\Parvaj\Util\Process;
use MAChitgarha\Parvaj\Util\ExecutableFinder;

use MAChitgarha\Parvaj\WaveformType;

use Symfony\Component\Console\Exception\RuntimeException;

use Symfony\Component\Filesystem\Path;

abstract class GhdlRunner
{
    protected string $workdir = "build";

    public function __construct(
        protected string $executable,
    ) {
    }

    public function setWorkdir(string $workdir): void
    {
        $this->workdir = $workdir;
    }

    public function analyze(array $unitFilePaths): void
    {
        (new Process(
            [
                $this->executable,
                "-a",
                ...OptionBuilder::build([
                    "workdir" => $this->workdir,
                ]),
                ...$unitFilePaths
            ]
        ))->runSafe();
    }

    /**
     * @todo Remove VCD file after this step, because of being huge in size.
     */
    public function elabRun(string $testEntityName, ElabRunUserOptions $userOptions): string
    {
        (new Process(
            [
                $this->executable,
                "--elab-run",
                ...OptionBuilder::build(
                    $this->getElabRunGeneralOptions($testEntityName)
                ),
                $testEntityName,
                ...OptionBuilder::build(
                    $this->getElabRunSimulationOptions(
                        $waveformFilePath = $this->generateWaveformFilePath(
                            $testEntityName,
                            $userOptions->getWaveformType(),
                        ),
                        $userOptions
                    )
                ),
            ]
        ))->runSafe();

        return $waveformFilePath;
    }

    protected function generateWaveformFilePath(string $testEntityName, string $waveformType): string
    {
        return Path::join($this->workdir, "$testEntityName.$waveformType");
    }

    protected function getElabRunGeneralOptions(string $testEntityName): array
    {
        return [
            "workdir" => $this->workdir,
        ];
    }

    protected function getElabRunSimulationOptions(string $waveformFilePath, ElabRunUserOptions $userOptions): array
    {
        $waveformOption = match ($userOptions->getWaveformType()) {
            WaveformType::VCD => ["vcd" => $waveformFilePath],
            WaveformType::GHW => ["wave" => $waveformFilePath],
        };

        return \array_merge(
            $waveformOption,
            $userOptions->getSimulationOptions(),
        );
    }

    public static function detectVersion(ExecutableFinder $executableFinder): GhdlVersion
    {
        $ghdlExecutable = $executableFinder->find("ghdl");

        $ghdlVersionProcess = new Process([$ghdlExecutable, "--version"]);
        $ghdlVersionProcess->run();
        $output = $ghdlVersionProcess->getCompleteOutput();

        if (\preg_match("/GHDL +((\d+)\.(\d+)(\.(\d+))?([\-_](dev))?)/i", $output, $match)) {
            $emptyToNull = fn($x) => $x !== "" ? $x : null;
            try {
                return new GhdlVersion(
                    $match[1],
                    (int)$match[2],
                    (int)$match[3],
                    (int)$emptyToNull($match[5]),
                    $emptyToNull($match[7]),
                );
            } catch (OutOfBoundsException) {
                throw new RuntimeException(
                    "The GHDL version is not supported yet" . PHP_EOL .
                    "Feel free to open an issue here: " . Application::ISSUES_PAGE_LINK
                );
            }
        }
        throw new RuntimeException("Cannot detect GHDL version");
    }
}

<?php

namespace MAChitgarha\Parvaj\Runner\Gtkwave;

use MAChitgarha\Parvaj\Config;
use MAChitgarha\Parvaj\Runner\OptionBuilder;
use MAChitgarha\Parvaj\Util\ExecutableFinder;
use MAChitgarha\Parvaj\WaveformType;
use MAChitgarha\Parvaj\Util\Process;

class GtkwaveRunner
{
    public function __construct(
        private ExecutableFinder $executableFinder,
        private Config $config,
    ) {
    }

    public function open(string $waveformFilePath, string $waveformType): void
    {
        if ($this->config->isNonNull(Config::KEY_GTKWAVE_CMDLINE)) {
            $command = [
                $this->config->get(Config::KEY_GTKWAVE_CMDLINE),
                $waveformFilePath,
            ];
        } else {
            $options = [];
            if ($waveformType === WaveformType::VCD) {
                $options["o"] = null;
            }

            $command = [
                $this->executableFinder->find("gtkwave"),
                ...OptionBuilder::build($options),
                $waveformFilePath,
            ];
        }

        (new Process($command))->runSafe();
    }
}

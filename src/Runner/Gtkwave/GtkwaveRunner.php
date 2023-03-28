<?php

namespace MAChitgarha\Parvaj\Runner\Gtkwave;

use MAChitgarha\Parvaj\Runner\OptionBuilder;

use MAChitgarha\Parvaj\WaveformType;

use MAChitgarha\Parvaj\Util\Process;

class GtkwaveRunner
{
    public function __construct(
        private string $executable,
    ) {
    }

    public function open(string $waveformFilePath, string $waveformType): void
    {
        $options = [];
        if ($waveformType === WaveformType::VCD) {
            $options["o"] = null;
        }

        (new Process(
            [
                $this->executable,
                ...OptionBuilder::build($options),
                $waveformFilePath,
            ]
        ))->run();
    }
}

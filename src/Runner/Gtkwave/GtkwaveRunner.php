<?php

namespace MAChitgarha\Parvaj\Runner\Gtkwave;

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
        (new Process(
            [
                $this->executable,
                $waveformFilePath,
            ]
        ))->run();
    }
}

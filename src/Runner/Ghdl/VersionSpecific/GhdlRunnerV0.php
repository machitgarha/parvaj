<?php

namespace MAChitgarha\Parvaj\Runner\Ghdl\VersionSpecific;

use MAChitgarha\Parvaj\Runner\Ghdl\GhdlRunner;

use Symfony\Component\Filesystem\Path;

class GhdlRunnerV0 extends GhdlRunner
{
    protected function getElabRunGeneralOptions(string $testEntityName): array
    {
        return parent::getElabRunGeneralOptions($testEntityName) + [
            "o" => Path::join($this->workdir, $testEntityName)
        ];
    }
}

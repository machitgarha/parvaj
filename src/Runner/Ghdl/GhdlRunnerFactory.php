<?php

namespace MAChitgarha\Parvaj\Runner\Ghdl;

use MAChitgarha\Parvaj\Config;

use MAChitgarha\Parvaj\Util\ExecutableFinder;

class GhdlRunnerFactory
{
    private const VERSION_TO_CLASS_MAPPER = [
        GhdlVersion::V0 => VersionSpecific\GhdlRunnerV0::class,
        GhdlVersion::V1 => VersionSpecific\GhdlRunnerV1::class,
        GhdlVersion::V2 => VersionSpecific\GhdlRunnerV2::class,
        GhdlVersion::V3 => VersionSpecific\GhdlRunnerV3::class,
    ];

    public static function create(ExecutableFinder $executableFinder, Config $config): GhdlRunner
    {
        $ghdlRunnerClass = self::VERSION_TO_CLASS_MAPPER[$config->getGhdlVersion()];

        return new $ghdlRunnerClass(
            $executableFinder->find("ghdl")
        );
    }
}

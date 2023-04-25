<?php

namespace MAChitgarha\Parvaj\Runner\Ghdl;

use MAChitgarha\Parvaj\Config;

use MAChitgarha\Parvaj\Util\ExecutableFinder;

use Symfony\Component\Console\Exception\InvalidArgumentException;

class GhdlRunnerFactory
{
    private const VERSION_TO_CLASS_MAPPER = [
        GhdlVersion::TYPE_0 => VersionSpecific\GhdlRunnerV0::class,
        GhdlVersion::TYPE_1 => VersionSpecific\GhdlRunnerV1::class,
    ];

    public static function create(ExecutableFinder $executableFinder, Config $config): GhdlRunner
    {
        // @phan-suppress-next-line PhanTypeMismatchDimFetch
        $ghdlRunnerClass = self::VERSION_TO_CLASS_MAPPER[$config->getGhdlVersion()]
            ?? throw new InvalidArgumentException(
                "GHDL version invalid or unsupported (config value set to '{$config->getGhdlVersion()}')"
            );

        return new $ghdlRunnerClass(
            $executableFinder->find("ghdl")
        );
    }
}

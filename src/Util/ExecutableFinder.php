<?php

namespace MAChitgarha\Parvaj\Util;

class ExecutableFinder extends \Symfony\Component\Process\ExecutableFinder
{
    public function find(string $name, string $default = null, array $extraDirs = []): string
    {
        return parent::find($name, $default, $extraDirs)
            ?? throw new \Exception("Could not find '$name' executable");
    }
}

<?php

namespace MAChitgarha\Parvaj\Util;

class File
{
    public static function read(string $path): string
    {
        $file = new \SplFileObject($path, 'r');

        return $file->fread(
            $file->getSize()
        );
    }
}

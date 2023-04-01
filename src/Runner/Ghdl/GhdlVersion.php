<?php

namespace MAChitgarha\Parvaj\Runner\Ghdl;

class GhdlVersion
{
    public const V0 = 0;
    public const V1 = 1;
    public const V2 = 2;
    public const V3 = 3;

    private const LIST = [
        self::V0,
        self::V1,
        self::V2,
        self::V3,
    ];

    public static function fromMajorVersion(int $majorVersion): int
    {
        return match ($majorVersion) {
            0 => self::V0,
            1 => self::V1,
            2 => self::V2,
            3 => self::V3,
            default => throw new \InvalidArgumentException(),
        };
    }
}

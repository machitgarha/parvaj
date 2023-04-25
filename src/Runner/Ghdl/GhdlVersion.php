<?php

namespace MAChitgarha\Parvaj\Runner\Ghdl;

use OutOfBoundsException;

class GhdlVersion
{
    public const TYPE_0 = "0";
    public const TYPE_1 = "1";

    public function __construct(
        private string $full,
        private int $major,
        private int $minor,
        private ?int $patch,
        private ?string $preRelease,
    ) {
        if (!(0 <= $major && $major <= 1)) {
            throw new OutOfBoundsException("GHDL version not supported");
        }

        $this->preRelease = \strtolower($this->preRelease);
    }

    public function getFull(): string
    {
        return $this->full;
    }

    public function getType(): string
    {
        if ($this->major === 1 && $this->preRelease === "dev") {
            return self::TYPE_0;
        } else {
            return (string)$this->major;
        }
    }
}

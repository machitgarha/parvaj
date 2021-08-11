<?php

namespace MAChitgarha\Parvaj;

use Webmozart\PathUtil\Path;

abstract class AbstractEntityFilePathGenerator
{
    public const VHDL_EXTENSION = 'vhd';

    private string $entityName;
    private string $groupName;

    public function __construct(string $entityName, string $groupName)
    {
        $this->entityName = $entityName;
        $this->groupName = $groupName;
    }

    public function generate(): string
    {
        return Path::join(
            $this->generateDirectoryPath(),
            $this->generateFileName(),
        );
    }

    public function generateDirectoryPath(): string
    {
        return Path::join(
            static::generateOperatingDirectoryPath(),
            $this->getGroupDirectory(),
        );
    }

    public static function generateOperatingDirectoryPath(): string
    {
        return Path::join(
            static::getRootDirectory(),
            static::getOperatingDirectory(),
        );
    }

    private static function getRootDirectory(): string
    {
        return \getcwd();
    }

    abstract protected static function getOperatingDirectory(): string;

    private function getGroupDirectory(): string
    {
        return $this->groupName;
    }

    private function generateFileName(): string
    {
        // TODO: Make this user-specified
        return \str_replace('_', '-', $this->entityName) . '.' .
            self::VHDL_EXTENSION;
    }
}

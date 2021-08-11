<?php

namespace MAChitgarha\Parvaj;

use Webmozart\PathUtil\Path;

abstract class AbstractUnitFilePathGenerator
{
    public const VHDL_EXTENSION = 'vhd';

    private string $unitName;
    private string $groupName;

    public function __construct(string $unitName, string $groupName)
    {
        $this->unitName = $unitName;
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
        return \str_replace('_', '-', $this->unitName) . '.' .
            self::VHDL_EXTENSION;
    }

    public static function locate(string $unitName): string
    {
        $groupDirectoriesIterator = new \DirectoryIterator(
            static::getOperatingDirectory()
        );

        foreach ($groupDirectoriesIterator as $groupDirectory) {
            if (
                !$item->isDot()
                && $groupDirectory->isDir()
            ) {
                $unitPath = (new static(
                    $unitName, $groupDirectory->getFilename()
                ))->generate();

                // Check if the entity we need exist in the current group or not
                if (\file_exists($unitPath)) {
                    return $unitPath;
                }
            }
        }

        throw new \RuntimeException(
            "Cannot find path of entity '$unitName'"
        );
    }
}

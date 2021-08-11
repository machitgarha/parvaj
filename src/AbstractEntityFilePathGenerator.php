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
            $this->generateOperatingDirectoryPath(),
            $this->getGroupDirectory(),
            $this->generateEntityFileName(),
        );
    }

    public function generateOperatingDirectoryPath(): string
    {
        return Path::join(
            $this->getRootDirectory(),
            $this->getOperatingDirectory(),
        );
    }

    private function getRootDirectory(): string
    {
        return \getcwd();
    }

    abstract protected function getOperatingDirectory(): string;

    private function getGroupDirectory(): string
    {
        return $this->groupName;
    }

    private function generateEntityFileName(): string
    {
        // TODO: Make this user-specified
        return \str_replace('_', '-', $this->entityName) . '.vhd';
    }
}

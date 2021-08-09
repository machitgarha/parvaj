<?php

namespace MAChitgarha\Parvaj\FileInfo;

use \Webmozart\PathUtil\Path;

abstract class AbstractEntityFileInfo
{
    protected const BASE_DIRECTORY = null;
    public const VHDL_EXTENSION = "vhd";

    protected string $filename;
    protected ?string $filePath = null;

    protected string $entityName;
    protected ?string $groupName = null;

    public function __construct(
        string $entityName,
        string $groupName = null
    ) {
        $this->entityName = $entityName;
        $this->groupName = $groupName;

        $this->filename = $this->generateFilename();
        $this->filePath = $this->generatePath();
    }

    protected function generateFilename(): string
    {
        return static::canonicalizeName($this->entityName) . "." . static::VHDL_EXTENSION;
    }

    protected function generatePath(): ?string
    {
        if ($this->groupName === null) {
            return null;
        }

        /*
         * If $this->filename is uninitialized, then an Error is thrown. Because this
         * class is abstract, it does not matter whether static::BASE_DIRECTORY is defined
         * here or not, as it cannot be instantiated; but, is expected to be defined in
         * children classes.
         */
        return Path::join(static::BASE_DIRECTORY, $this->groupName, $this->filename);
    }

    protected static function canonicalizeName(string $filename): string
    {
        return \str_replace("_", "-", $filename);
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getPath(): string
    {
        if ($this->filePath === null) {
            throw new \Exception(
                "Cannot return file path: Group name is empty"
            );
        }

        return Path::canonicalize($this->filePath);
    }

    public function findPath(): self
    {
        $directoryIterator = new \DirectoryIterator(static::BASE_DIRECTORY);

        foreach ($directoryIterator as $item) {
            if (
                $item->isDir() && !$item->isDot() &&
                \file_exists($path = Path::join($item->getPathname(), $this->filename))
            ) {
                $this->filePath = $path;
                return $this;
            }
        }

        throw new \Exception("Cannot find path of entity '{$this->entityName}'");
    }
}

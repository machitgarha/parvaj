<?php

namespace MAChitgarha\Parvaj\EntityCreation;

abstract class AbstractEntityFileCreator
{
    protected const TEMPLATES_DIR = __DIR__ . "/../templates";
    protected const ERR_FILE_EXISTS = "VHDL file already exists.";

    protected string $path;
    protected string $contents = "";

    protected string $entityName;
    protected string $groupName;
    protected string $architectureName;

    public function __construct(
        string $entityName,
        string $groupName,
        string $architectureName = "structural"
    ) {
        $this->entityName = $entityName;
        $this->groupName = $groupName;
        $this->architectureName = $architectureName;

        $this->path = $this->generatePath();
        $this->contents = $this->generateContents();
    }

    abstract protected function generatePath(): string;
    abstract protected function generateContents(): string;

    protected static function ensureNotExists(string $filePath): void
    {
        if (file_exists($filePath)) {
            throw new \Exception(static::ERR_FILE_EXISTS);
        }
    }

    protected static function createParentDirectories(string $filePath): void
    {
        $dir = \dirname($filePath);
        if (!\is_dir($dir) && !@\mkdir(dirname($filePath), 0755, true)) {
            throw new \Exception("Cannot create file's parent directories ($filePath).");
        }
    }

    protected static function replacePlaceholders(
        string $templateString,
        array $replacementMappings
    ): string {
        return \str_replace(
            \array_map(fn ($i) => "<$i>", \array_keys($replacementMappings)),
            \array_values($replacementMappings),
            $templateString
        );
    }

    public function write(): void
    {
        self::ensureNotExists($this->path);
        self::createParentDirectories($this->path);

        $file = new \SplFileObject($this->path, "w");
        $file->fwrite($this->contents);
    }
}

<?php

declare(strict_types = 1);

require __DIR__ . "/../vendor/autoload.php";

use Webmozart\PathUtil\Path;

abstract class EntityFileInfo
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
        return static::BASE_DIRECTORY . "/{$this->groupName}/{$this->filename}";
    }

    protected static function canonicalizeName(string $filename): string
    {
        return str_replace("_", "-", $filename);
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
        $directoryIterator = new DirectoryIterator(static::BASE_DIRECTORY);

        foreach ($directoryIterator as $item) {
            if (
                $item->isDir() && !$item->isDot() &&
                file_exists($path = $item->getPathname() . "/{$this->filename}")
            ) {
                $this->filePath = $path;
                return $this;
            }
        }

        throw new \Exception("Cannot find path of entity '{$this->entityName}'");
    }
}

class SourceEntityFileInfo extends EntityFileInfo
{
    protected const BASE_DIRECTORY = __DIR__ . "/../../src";
}

class UnitTestEntityFileInfo extends EntityFileInfo
{
    protected const BASE_DIRECTORY = __DIR__ . "/../../tests/unit";
}

abstract class EntityFileCreator
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
        $dir = dirname($filePath);
        if (!is_dir($dir) && !@mkdir(dirname($filePath), 0755, true)) {
            throw new \Exception("Cannot create file's parent directories ($filePath).");
        }
    }

    protected static function replacePlaceholders(
        string $templateString,
        array $replacementMappings
    ): string {
        return str_replace(
            array_map(fn ($i) => "<$i>", array_keys($replacementMappings)),
            array_values($replacementMappings),
            $templateString
        );
    }

    public function write(): void
    {
        self::ensureNotExists($this->path);
        self::createParentDirectories($this->path);

        $file = new SplFileObject($this->path, "w");
        $file->fwrite($this->contents);
    }
}

class SourceEntityFileCreator extends EntityFileCreator
{
    protected const TEMPLATE_FILE_PATH = parent::TEMPLATES_DIR . "/source-entity.vhd";

    protected function generatePath(): string
    {
        return (new SourceEntityFileInfo($this->entityName, $this->groupName))->getPath();
    }

    protected function generateContents(): string
    {
        $file = new SplFileObject(self::TEMPLATE_FILE_PATH, "r");

        return self::replacePlaceholders($file->fread($file->getSize()), [
            "entity-name" => $this->entityName,
            "architecture-name" => $this->architectureName,
        ]);
    }
}

class UnitTestEntityFileCreator extends EntityFileCreator
{
    protected const ERR_FILE_EXISTS = "Unit-test VHDL file already exists.";

    private const TEMPLATE_FILE_PATH = parent::TEMPLATES_DIR . "/unit-test-entity.vhd";

    private string $testEntityName;

    public function __construct(
        string $entityName,
        string $groupName,
        string $architectureName = "structural"
    ) {
        $this->testEntityName = "test_$entityName";

        parent::__construct($entityName, $groupName, $architectureName);

        echo $this->path . PHP_EOL;
    }

    protected function generatePath(): string
    {
        return (new UnitTestEntityFileInfo(
            $this->testEntityName, $this->groupName
        ))->getPath();
    }

    protected function generateContents(): string
    {
        $file = new SplFileObject(self::TEMPLATE_FILE_PATH, "r");

        return self::replacePlaceholders($file->fread($file->getSize()), [
            "entity-name" => $this->testEntityName,
            "architecture-name" => $this->architectureName,
            "component" => $this->getSourceEntityAsComponent(),
            "source-entity-name" => $this->entityName,
        ]);
    }

    private function getSourceEntityAsComponent(): string
    {
        $sourceEntityPath = (new SourceEntityFileCreator(
            $this->entityName,
            $this->groupName,
            $this->architectureName
        ))->generatePath();

        try {
            $sourceEntityFile = new SplFileObject($sourceEntityPath, "r");
        } catch (\RuntimeException $e) {
            throw new \Exception(
                "Source entity file does not exist ($sourceEntityPath)."
            );
        }

        $contents = $sourceEntityFile->fread($sourceEntityFile->getSize());

        if (!preg_match("/(entity)[\s\S]*(entity;)/i", $contents, $matches)) {
            throw new \Exception(
                "Source file does not contain any entities ($sourceEntityPath)."
            );
        }

        return str_replace("entity", "component", implode(PHP_EOL, array_map(
            fn ($i) => empty($i) ? "" : ("    " . $i),
            explode(PHP_EOL, $matches[0])
        )));
    }
}

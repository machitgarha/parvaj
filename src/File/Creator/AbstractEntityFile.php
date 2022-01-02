<?php

namespace MAChitgarha\Parvaj\File\Creator;

abstract class AbstractEntityFile
{
    private AbstractFilePath $filePathGenerator;
    private AbstractEntityContent $contentGenerator;

    private string $entityName;
    private string $groupName;
    private string $architectureName;

    public function __construct(
        AbstractFilePath $filePathGenerator,
        AbstractEntityContent $contentGenerator,
        string $entityName,
        string $groupName,
        string $architectureName
    ) {
        $this->filePathGenerator = $filePathGenerator;
        $this->contentGenerator = $contentGenerator;

        $this->entityName = $entityName;
        $this->groupName = $groupName;
        $this->architectureName = $architectureName;
    }

    public function create(): self
    {
        return $this
            ->makeBaseDirectory()
            ->ensureNotExists()
            ->writeFile();
    }

    /**
     * Make the directory the entity file live in.
     */
    private function makeBaseDirectory(): self
    {
        $baseDirectoryPath = $this->filePathGenerator->generateDirectoryPath();

        if (
            !\is_dir($baseDirectoryPath)
            && !@\mkdir($baseDirectoryPath, 0755, true)
        ) {
            throw new \RuntimeException(
                "Could not create directory '$baseDirectoryPath'"
            );
        }

        return $this;
    }

    private function ensureNotExists(): self
    {
        $entityFilePath = $this->filePathGenerator->generate();

        if (\file_exists($entityFilePath)) {
            throw new \RuntimeException(
                "The entity file already exists at '$entityFilePath'"
            );
        }

        return $this;
    }

    private function writeFile(): self
    {
        $file = new \SplFileObject(
            $this->filePathGenerator->generate(),
            "w"
        );
        $file->fwrite(
            $this->contentGenerator->generate()
        );

        return $this;
    }
}

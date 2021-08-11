<?php

namespace MAChitgarha\Parvaj;

use MAChitgarha\Parvaj\Util\File;

class DependencyResolver
{
    private const REGEX_COMPONENT_FINDER = '/component\s+([a-z0-9_]+)/is';
    private const REGEX_PACKAGE_FINDER = '/use\s+work.(\w+).\w+;/is';

    private string $mainUnitTestFilePath;

    public function __construct(string $mainUnitTestFilePath)
    {
        $this->mainUnitTestFilePath = $mainUnitTestFilePath;
    }

    public function resolve(): \Generator
    {
        yield from $this->findAllDependenciesOf(
            $this->mainUnitTestFilePath,
            [$this->mainUnitTestFilePath]
        );
    }

    private function findDependencyUnitPathsRecursive(
        string $currentUnitPath,
        array $parentUnitPaths
    ): \Generator {
        foreach (
            self::extractDependencyUnitNames($currentUnitPath) as $depUnitName
        ) {
            $depUnitPath = self::findSourceUnitPath($unitName);

            // Prevent from infinite recursion
            if (\in_array($depUnitPath, $parentUnitPaths)) {
                yield $depUnitPath;
            } else {
                yield from self::findDependencyUnitPathsRecursive(
                    $depUnitPath,
                    [
                        ...$parentUnitPaths,
                        ...$currentUnitPath,
                    ]
                );
            }
        }

        yield $currentUnitPath;
    }

    private static function extractDependencyUnitNames(
        string $unitPath
    ): \Generator {
        $unitContents = File::read($unitPath);

        yield from self::extractDependencyComponentNames($unitContents);
        yield from self::extractDependencyPackageNames($unitContents);
    }

    private static function extractDependencyComponentNames(
        string $unitContents
    ): \Generator {
        yield from self::extractDependencyUnitUsingRegex(
            $unitContents,
            self::REGEX_COMPONENT_FINDER
        );
    }

    private static function extractDependencyPackageNames(
        string $unitContents
    ): \Generator {
        yield from self::extractDependencyUnitUsingRegex(
            $unitContents,
            self::REGEX_PACKAGE_FINDER
        );
    }

    private static function extractDependencyUnitUsingRegex(
        string $unitContents,
        string $regex
    ): \Generator {
        if (preg_match_all($regex, $unitContents, $matches)) {
            yield from $matches[1];
        } else {
            yield from [];
        }
    }

    private static function findSourceUnitPath(string $unitName): string
    {
        $groupDirectoriesIterator = new \DirectoryIterator(
            SourceUnitFilePathGenerator::getOperatingDirectory()
        );

        foreach ($groupDirectoriesIterator as $groupDirectory) {
            if (
                !$item->isDot()
                && $groupDirectory->isDir()
            ) {
                // Check if the entity we need exist in the current group or not
                if (\file_exists(
                    $unitPath = (new SourceUnitFilePathGenerator(
                        $unitName, $groupDirectory->getFilename()
                    ))->generate()
                )) {
                    return $unitPath;
                }
            }
        }

        throw new \RuntimeException(
            "Cannot find path of entity '$unitName'"
        );
    }
}

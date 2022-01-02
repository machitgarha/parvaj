<?php

namespace MAChitgarha\Parvaj;

use MAChitgarha\Parvaj\File\PathGenerator\SourceFilePath;
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
        yield from self::findDependencyPathsRecursive(
            $this->mainUnitTestFilePath,
            [$this->mainUnitTestFilePath]
        );
    }

    private static function findDependencyPathsRecursive(
        string $currentPath,
        array $parentPaths
    ): \Generator {
        foreach (
            self::extractDependencyNames($currentPath) as $dependencyName
        ) {
            $dependencyPath = SourceFilePath::locate($dependencyName);

            // Prevent from infinite recursion
            if (\in_array($dependencyPath, $parentPaths)) {
                yield $dependencyPath;
            } else {
                yield from self::findDependencyPathsRecursive(
                    $dependencyPath,
                    [...$parentPaths, $dependencyPath]
                );
            }
        }

        yield $currentPath;
    }

    private static function extractDependencyNames(
        string $filePath
    ): \Generator {
        $fileContents = File::read($filePath);

        yield from self::extractDependencyComponentNames($fileContents);
        yield from self::extractDependencyPackageNames($fileContents);
    }

    private static function extractDependencyComponentNames(
        string $fileContents
    ): \Generator {
        yield from self::extractDependencyUsingRegex(
            $fileContents,
            self::REGEX_COMPONENT_FINDER
        );
    }

    private static function extractDependencyPackageNames(
        string $fileContents
    ): \Generator {
        yield from self::extractDependencyUsingRegex(
            $fileContents,
            self::REGEX_PACKAGE_FINDER
        );
    }

    private static function extractDependencyUsingRegex(
        string $fileContents,
        string $regex
    ): \Generator {
        if (preg_match_all($regex, $fileContents, $matches)) {
            yield from $matches[1];
        } else {
            yield from [];
        }
    }
}

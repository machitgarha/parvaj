g<?php

namespace MAChitgarha\Parvaj;

use MAChitgarha\Parvaj\DependencyResolver\Regex;
use MAChitgarha\Parvaj\Util\File;

class DependencyResolver
{
    /**
     * The path of the initial file. It should be a unit-test file.
     */
    private string $initFilePath;

    public function __construct(string $initFilePath)
    {
        $this->initFilePath = $initFilePath;
    }

    public function resolve(): \Generator
    {
        yield from self::findDependencyPathsRecursive(
            $this->initFilePath,
            [$this->initFilePath]
        );
    }

    private static function findDependencyPathsRecursive(
        string $path,
        array $scannedPaths
    ): \Generator {
        foreach (self::extractDependencyNames($path) as $dependencyName) {
            $dependencyPath = PathFinder::find($dependencyName);

            // Prevent from infinite recursion
            if (\in_array($dependencyPath, $scannedPaths)) {
                yield $dependencyPath;
            } else {
                yield from self::findDependencyPathsRecursive(
                    $dependencyPath,
                    [...$scannedPaths, $dependencyPath]
                );
            }
        }

        yield $path;
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
            Regex::COMPONENT
        );
    }

    private static function extractDependencyPackageNames(
        string $fileContents
    ): \Generator {
        yield from self::extractDependencyUsingRegex(
            $fileContents,
            Regex::USE_PACKAGE
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

// Inner classes
namespace MAChitgarha\Parvaj\DependencyResolver;

class Regex
{
    public const COMPONENT = "/component\s+([a-z0-9_]+)/i";
    public const USE_PACKAGE = "/use\s+work\.(\w+)\.\w+;/i";
}

<?php

namespace MAChitgarha\Parvaj;

use MAChitgarha\Parvaj\DependencyResolver\Regex;

class DependencyResolver
{
    public function __construct(
        private PathFinder $pathFinder,
    ) {}

    /**
     * Returns the list of all dependencies, in order.
     *
     * @param string $initialUnit Name of the unit as the starting point.
     * @return array The first element of the list means the most dependent, and
     * the last one is the least (i.e. it is the path of the initial unit
     * actually).
     */
    public function resolve(string $initialUnit): array
    {
        return \array_unique(\iterator_to_array(
            $this->findDependencyPathsRecursive(
                $initialUnitPath = $this->pathFinder->find($initialUnit),
                [$initialUnitPath]
            ),
            false
        ));
    }

    private function findDependencyPathsRecursive(
        string $path,
        array $scannedPaths
    ): \Generator {
        foreach (self::extractDependencyNames($path) as $dependencyName) {
            $dependencyPath = $this->pathFinder->find($dependencyName);

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
        $fileContents = \file_get_contents($filePath);

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

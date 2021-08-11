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
        yield from self::findDependencyUnitPathsRecursive(
            $this->mainUnitTestFilePath,
            [$this->mainUnitTestFilePath]
        );
    }

    private static function findDependencyUnitPathsRecursive(
        string $currentUnitPath,
        array $parentUnitPaths
    ): \Generator {
        foreach (
            self::extractDependencyUnitNames($currentUnitPath) as $depUnitName
        ) {
            $depUnitPath = SourceUnitFilePathGenerator::locate($depUnitName);

            // Prevent from infinite recursion
            if (\in_array($depUnitPath, $parentUnitPaths)) {
                yield $depUnitPath;
            } else {
                yield from self::findDependencyUnitPathsRecursive(
                    $depUnitPath,
                    [
                        ...$parentUnitPaths,
                        $depUnitPath,
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
}

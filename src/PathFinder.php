<?php

namespace MAChitgarha\Parvaj;

use SplFileObject;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use MAChitgarha\Parvaj\PathFinder\Cache\{
    Root as CacheRoot,
    Unit,
    UnitType,
    SnapshotInfo,
};
use MAChitgarha\Parvaj\PathFinder\Regex;
use MAChitgarha\Phirs\DirectoryProviderFactory;
use MAChitgarha\Phirs\Util\Platform;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\Filesystem\Path;

class PathFinder
{
    private string $rootPath;

    /**
     * The cache to minimize filesystem hit.
     *
     * When the path of a unit is requested, repeating the search process by
     * scanning all available files every time is a huge deal. To prevent this,
     * we define a cache mechanism.
     *
     * Here, we mainly define the cache structure, and different processes like
     * cache validation are either self-documented or described elsewhere.
     *
     * At the root, we have a list of visited paths, stored in "@cached_paths"
     * key. All paths are absolute. This is useful to find new or moved files
     * (i.e. not-scanned paths).
     *
     * Every other key refers to a scanned unit. Each of these entries has the
     * following keys in itself:
     *
     * - "path": The absolute path of the unit.
     *
     * - "snapshot_info": Information of the last snapshot of the related
     *   portion of the file used to identify the name of the unit. Useful for
     *   cache (in)validation by comparing it to its current state. It consists
     *   of its start position ("start") and length ("length"), and its content
     *   ("content").
     *
     *   For example, for a file containing an entity named "something", it is
     *   something like "entity something ". Notice the ending space, it is used
     *   to ensure the entity has not been renamed to something else, like
     *   "something_else".
     *
     * - "type": The unit type. One of UnitType::* constants.
     */
    private FilesystemAdapter $cache;

    /**
     * @param string $rootPath The root of the path to find in (i.e project
     * path).
     */
    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
        $this->cache = self::makeCache($rootPath);
    }

    private static function makeCachePath()
    {
        return Path::join(
            DirectoryProviderFactory::createStandard(
                Platform::autoDetect()
            )->getCachePath(),
            'parvaj',
        );
    }

    private static function makeCache(string $rootPath): FilesystemAdapter
    {
        return new FilesystemAdapter($rootPath, 0, self::makeCachePath());
    }

    /**
     * Finds the path of a unit.
     *
     * @return string Path of the requested unit name.
     */
    public function find(string $unitName): string
    {
        if ($this->cache->hasItem($unitName)) {
            return $this->findCached($unitName);
        } else {
            return $this->findNotCached($unitName);
        }
    }

    /**
     * Finds the path of a unit where it's already cached.
     */
    private function findCached(string $unitName): string
    {
        $cacheItem = $this->cache->getItem($unitName);
        $path = $cacheItem->get()[UnitKey::PATH];

        if (\file_exists($path)) {
            /*
             * The file is expected to be readable, so otherwise an exception
             * must be thrown.
             */
            $file = new SplFileObject($path, "r");

            return $this->findCachedWithExistentFile($cacheItem, $file);
        } else {
            return $this->findNotCached($unitName);
        }
    }

    private function makeCacheAbsolutePath(array $cache): string
    {
        return $this->getAbsolutePathOf();
    }

    private function findCachedWithExistentFile(
        ItemInterface $cacheItem,
        SplFileObject $file
    ): string {
        $cache = $cacheItem->get();
        $path = $file->getPathname();

        if (self::isSnapshotUnchanged($cache, $file)) {
            return $path;
        }

        if (($updatedSnapshotInfo = self::searchUnitInFile(
            $file,
            $unitName,
            $cache[Unit::TYPE]
        )) !== null) {
            $cache[Unit::SNAPSHOT_INFO] = $updatedSnapshotInfo;
            $cacheItem->set($cache);
            $this->cache->save($cacheItem);

            return $path;
        }

        return $this->findNotCached($unitName);
    }

    /**
     * Tells whether the main slice of the cached file remained the same as the
     * cached version or not.
     */
    private static function isSnapshotUnchanged(
        array $cache,
        SplFileObject $file
    ): bool {
        $snapshotInfo = $cache[UnitKey::SNAPSHOT_INFO];

        return self::getSnapshotFromFile(
            $file,
            $snapshotInfo[SnapshotInfo::START],
            $snapshotInfo[SnapshotInfo::LENGTH]
        ) === $snapshotInfo[SnapshotInfo::CONTENT];
    }

    private static function getSnapshotFromFile(
        SplFileObject $file,
        int $from,
        int $length
    ): string {
        $file->rewind();
        $file->fseek($from);
        return $file->fread($length);
    }

    /**
     * Searches for a specific unit in a file.
     *
     * @param string $unitType One of the UnitType constant.
     * @return array|null The updated snapshot info if the unit is found, null
     * otherwise.
     */
    private static function searchUnitInFile(
        SplFileObject $file,
        string $unitName,
        string $unitType
    ): ?array {
        $pattern = Regex::for($unitType, $unitName);
        $fileContents = $file->fread($file->getSize());

        if (preg_match($pattern, $fileContents, $match, \PREG_OFFSET_CAPTURE)) {
            return SnapshotInfo::make(
                $match[0][1],
                \strlen($match[0][0]),
                $match[0][0]
            );
        }

        return null;
    }

    /**
     * Finds the path of a unit where it's not been cached.
     */
    private function findNotCached(string $unitName): string
    {
        $fileList = $this->getFileList();
        $cachedFileList = $this->cache->get(
            CacheRoot::CACHED_PATHS,
            fn($_) => []
        );
        $notCachedFileList = \array_diff($fileList, $cachedFileList);

        $this->updateCacheForPaths($notCachedFileList);
        if ($this->cache->hasItem($unitName)) {
            return $this->cache->getItem($unitName)->get()[Unit::PATH];
        }

        $this->updateCacheForPaths($cachedFileList);
        if ($this->cache->hasItem($unitName)) {
            return $this->cache->getItem($unitName)->get()[Unit::PATH];
        }

        throw new \RuntimeException("Path of unit '$unitName' not found.");
    }

    /**
     * Returns paths of all files available in the root directory, recursively.
     *
     * Paths are absolute.
     *
     * @return string[]
     */
    private function getFileList(): array
    {
        $fileIt = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
            $this->rootPath,
            FilesystemIterator::SKIP_DOTS
        ));

        $pathList = [];
        foreach ($fileIt as $file) {
            $pathList[] = $file->getRealPath();
        }

        return $pathList;
    }

    /**
     * Updates the cache for a specific list of files.
     *
     * @param string[] $paths Absolute paths of the files to get scanned.
     */
    private function updateCacheForPaths(array $paths): void
    {
        foreach ($paths as $path) {
            $matchesCount = preg_match_all(
                Regex::UNIT,
                \file_get_contents($path),
                $matches,
                \PREG_OFFSET_CAPTURE
            );

            for ($i = 0; $i < $matchesCount; $i++) {
                $cache = Unit::make(
                    $path,
                    SnapshotInfo::make(
                        $matches[0][$i][1],
                        \strlen($matches[0][$i][0]),
                        $matches[0][$i][0],
                    ),
                    UnitType::fromKeyword($matches[1][$i][0])
                );
                $unitName = $matches[2][$i][0];

                // Updates the cache implicitly
                $this->cache->get($unitName, fn($_) => $cache);
            }
        }
    }
}

// Inner classes
namespace MAChitgarha\Parvaj\PathFinder\Cache;

class Root
{
    public const CACHED_PATHS = "@cached_paths";
}

class Unit
{
    public const PATH = "path";
    public const SNAPSHOT_INFO = "snapshot_info";
    public const TYPE = "type";

    public static function make(
        string $path,
        array $snapshotInfo,
        string $type
    ): array {
        return [
            self::PATH => $path,
            self::SNAPSHOT_INFO => $snapshotInfo,
            self::TYPE => $type,
        ];
    }
}

class SnapshotInfo
{
    public const START = "start";
    public const LENGTH = "length";
    public const CONTENT = "content";

    public static function make(int $start, int $length, string $content): array
    {
        return [
            self::START => $start,
            self::LENGTH => $length,
            self::CONTENT => $content,
        ];
    }
}

class UnitType
{
    public const ENTITY = "entity";
    public const PACAKGE = "package";

    /**
     * Makes a unit type from a VHDL keyword.
     */
    public static function fromKeyword(string $keyword): string
    {
        $lowercaseKeyword = \strtolower($keyword);

        switch ($lowercaseKeyword) {
            case "entity":
                return self::ENTITY;
                // No break

            case "package":
                return self::PACAKGE;
                // No break

            default:
                throw new \InvalidArgumentException(
                    "Cannot convert keyword '$keyword' to a valid unit type"
                );
                // No break
        }
    }
}

namespace MAChitgarha\Parvaj\PathFinder;

class Regex
{
    public const UNIT = "/(entity|package)\s+(\w+)\s/i";

    public static function for(string $unitType, string $unitName)
    {
        if ($unitType === UnitType::ENTITY) {
            return self::entity($unitName);
        } elseif ($unitType === UnitType::PACAKGE) {
            return self::package($unitName);
        }
        throw new \InvalidArgumentException("Invalid unit type $unitType");
    }

    public static function entity(string $unitName): string
    {
        return "/entity\s+$unitName\s/i";
    }

    public static function package(string $unitName = null): string
    {
        return "/package\s+$unitName\s/i";
    }
}

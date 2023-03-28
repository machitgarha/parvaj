<?php

namespace MAChitgarha\Parvaj;

use MAChitgarha\Component\Pusheh;

use MAChitgarha\Phirs\DirectoryProviderFactory;

use MAChitgarha\Phirs\Util\Platform;

use Noodlehaus\Parser\Json;

final class Config
{
    private const FILE_NAME = "config.json";

    public const KEY_GHDL_VERSION = "ghdl.version";

    public string $filePath;
    private \Noodlehaus\Config $config;

    public function __construct()
    {
        $dir = DirectoryProviderFactory::createStandard(Platform::autoDetect())->getConfigPath() . "/parvaj";
        Pusheh::createDirRecursive($dir);

        $this->filePath = $dir . "/" . self::FILE_NAME;
        $this->config = new \Noodlehaus\Config($this->filePath, new Json());
    }

    private function get(string $key): mixed
    {
        return $this->config->get($key)
            ?? throw new \Exception("Config '$key' not set");
    }

    private function set(string $key, mixed $value)
    {
        $this->config->set($key, $value);
    }

    public function getGhdlVersion(): int
    {
        return (int)($this->get(self::KEY_GHDL_VERSION));
    }

    public function setGhdlVersion(int $ghdlVersion): void
    {
        $this->set(self::KEY_GHDL_VERSION, $ghdlVersion);
    }
}

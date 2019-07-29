<?php

class D9VK
{
    private $config;
    private $command;
    private $network;
    private $fs;
    private $wine;

    /**
     * D9VK constructor.
     * @param Config $config
     * @param Command $command
     * @param Network $network
     * @param FileSystem $fs
     * @param Wine $wine
     */
    public function __construct(Config $config, Command $command, Network $network, FileSystem $fs, Wine $wine)
    {
        $this->command = $command;
        $this->config  = $config;
        $this->network = $network;
        $this->fs      = $fs;
        $this->wine    = $wine;
    }

    public function version()
    {
        $d9vk = $this->config->wine('DRIVE_C') . '/d9vk';

        if (file_exists($d9vk)) {
            return trim(file_get_contents($d9vk));
        }

        return '';
    }

    public function versionRemote()
    {
        if ($this->config->get('script', 'd9vk_version')) {
            return $this->config->get('script', 'd9vk_version');
        }

        static $version;

        if (null === $version && ($latest = $this->getLatest())) {
            $version = $latest['version'];
        }

        return $version;
    }

    /**
     * @return array
     */
    public function getRepoData()
    {
        static $data;

        if (null === $data) {
            $data = $this->network->getJSON('https://api.github.com/repos/Joshua-Ashton/d9vk/releases');
        }

        return $data;
    }

    /**
     * @param callable|null $logCallback
     * @return bool
     */
    public function update($logCallback = null)
    {
        if (!Network::isConnected() || !$this->config->isD9vk() || !file_exists($this->config->wine('WINEPREFIX'))) {
            return false;
        }

        $branch = $this->config->get('script', 'd9vk_version')?:'';
        $oldVersion = $this->version();

        if ('' !== $oldVersion && false === $this->config->isD9vkAutoupdate()) {
            return false;
        }

        $newVersion = $this->versionRemote();

        (new DXVK($this->config, $this->command, $this->network))->updateDxvkConfig();

        $d9vk = $this->config->wine('DRIVE_C') . '/d9vk';
        $log  = $this->config->wine('WINEPREFIX') . '/winetricks.log';

        if (file_exists($log)) {
            $winetricks = array_filter(array_map('trim', explode("\n", file_get_contents($log))),
                function ($n) {return !$n && $n !== 'd9vk';});
            file_put_contents($log, implode("\n", $winetricks));
        }

        if ($newVersion !== $oldVersion && $newVersion) {
            if ($this->install($branch, $logCallback)) {
                file_put_contents($d9vk, $newVersion);
                $this->wine->winetricks(['d3dcompiler_43', 'd3dx9']);

                if ($logCallback) {
                    $logCallback("D9VK updated to {$newVersion}.");
                }
            }

            return true;
        }

        return false;
    }

    /**
     * @param null|string $version
     * @param null|callable $logCallback
     * @return bool
     */
    public function install($version = null, $logCallback = null)
    {
        $releases = $this->getList();
        $release  = $version && isset($releases[$version]) ? $releases[$version] : $this->getLatest();

        if (!$release) {
            return false;
        }

        $pathFile = $this->fs->download($release['url'], $this->config->getCacheDir());
        $dir      = $this->config->getCacheDir() . '/d9vk';

        $this->fs->unpack($pathFile, $dir);

        $dlls = [];

        if (file_exists($this->config->getWineSystem32Folder())) {
            foreach (glob("{$dir}/x32/*.dll") as $path) {
                $fileName = basename($path);
                $out = $this->config->getWineSystem32Folder() . "/{$fileName}";
                if (file_exists($out)) {
                    $this->fs->rm($out);
                }

                $this->fs->cp($path, $out);

                if (!in_array($fileName, $dlls, true)) {
                    $dlls[] = $fileName;
                }
            }
        }
        if (file_exists($this->config->getWineSyswow64Folder())) {
            foreach (glob("{$dir}/x64/*.dll") as $path) {
                $fileName = basename($path);
                $out = $this->config->getWineSyswow64Folder() . "/{$fileName}";
                if (file_exists($out)) {
                    $this->fs->rm($out);
                }

                $this->fs->cp($path, $out);

                if (!in_array($fileName, $dlls, true)) {
                    $dlls[] = $fileName;
                }
            }
        }

        if ($dlls) {
            foreach ($dlls as $dll) {
                if ($logCallback) {
                    $logCallback("Register {$dll}");
                }

                $this->register($dll);
            }
        }

        if (file_exists($dir)) {
            $this->fs->rm($dir);
        }

        return true;
    }

    /**
     * @return array
     */
    public function getList()
    {
        $releases = [];

        foreach ($this->getRepoData() as $release) {
            $item = [
                'version' => $release['tag_name'],
            ];

            foreach ($release['assets'] as $asset) {
                $item['url'] = $asset['browser_download_url'];
                break;
            }

            $releases[$item['version']] = $item;
        }

        return $releases;
    }

    /**
     * @return array|null
     */
    public function getLatest()
    {
        $releases = $this->getList();

        return $releases ? reset($releases) : null;
    }

    public function register($fileName)
    {
        $this->wine->regsvr32([$fileName]);
        $this->wine->run(['reg', 'add', 'HKEY_CURRENT_USER\\Software\\Wine\\DllOverrides', '/v', $fileName, '/d', 'native', '/f']);
    }
}
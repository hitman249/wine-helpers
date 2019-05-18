<?php

class D9VK
{
    private $config;
    private $command;
    private $network;

    /**
     * D9VK constructor.
     * @param Config $config
     * @param Command $command
     * @param Network $network
     */
    public function __construct(Config $config, Command $command, Network $network)
    {
        $this->command = $command;
        $this->config  = $config;
        $this->network = $network;
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

        if (null === $version) {
            if ($version = $this->network->getJSON('https://api.github.com/repos/Joshua-Ashton/d9vk/releases')) {
                $version = reset($version);
                $version = $version['tag_name'];
            } else {
                $version = '';
            }
        }

        return $version;
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

        $branch = $this->config->get('script', 'd9vk_version')?:'d9vk_master';
        $oldVersion = $this->version();

        if ('' !== $oldVersion && false === $this->config->isD9vkAutoupdate()) {
            return false;
        }

        $newVersion = 'd9vk_master' === $branch ? $this->getLatestBuildNumber() : $this->versionRemote();

        (new DXVK($this->config, $this->command, $this->network))->updateDxvkConfig();

        $d9vk = $this->config->wine('DRIVE_C') . '/d9vk';
        $log  = $this->config->wine('WINEPREFIX') . '/winetricks.log';

        if (file_exists($log)) {
            $winetricks = array_filter(array_map('trim', explode("\n", file_get_contents($log))),
                function ($n) {return !$n && $n !== 'd9vk';});
            file_put_contents($log, implode("\n", $winetricks));
        }

        if ($newVersion !== $oldVersion) {
            (new Wine($this->config, $this->command))->winetricks([$branch]);
            file_put_contents($d9vk, $newVersion);

            if ($logCallback) {
                $logCallback("D9VK updated to {$newVersion}.");
            }

            return true;
        }

        return false;
    }

    public function getLatestBuildNumber()
    {
        $url = 'https://git.froggi.es/joshua/d9vk/-/jobs/artifacts/master/download?job=d9vk';

        try {
            $request  = new \Rakit\Curl\Curl($url);
            $request->header('User-Agent', $this->config->getContextOptions('User-Agent'));
            $response = $request->get();
        } catch (ErrorException $e) {
            try {
                sleep(1);
                $response = $request->get();
            } catch (ErrorException $e) {
                try {
                    sleep(3);
                    $response = $request->get();
                } catch (ErrorException $e) {
                    return '';
                }
            }
        }

        if ($request && !$response->error()) {
            $headers = $response->getHeaders();
            $version = array_filter(explode('-/jobs', $headers['location']));
            $version = array_filter(explode('/', end($version)));
            $version = reset($version);

            return "{$version} build";
        }

        return '';
    }
}
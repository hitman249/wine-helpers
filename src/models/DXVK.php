<?php

class DXVK
{
    private $config;
    private $command;
    private $network;

    /**
     * DXVK constructor.
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
        $dxvk = $this->config->wine('DRIVE_C') . '/dxvk';

        if (file_exists($dxvk)) {
            return trim(file_get_contents($dxvk));
        }

        return '';
    }

    public function versionRemote()
    {
        if ($this->config->get('script', 'dxvk_version')) {
            return $this->config->get('script', 'dxvk_version');
        }

        static $version;

        if (null === $version) {
            $version = trim($this->network->get('https://raw.githubusercontent.com/doitsujin/dxvk/master/RELEASE'), " \t\n\r");
        }

        return $version;
    }

    /**
     * @param callable|null $logCallback
     * @return bool
     */
    public function update($logCallback = null)
    {
        if (!Network::isConnected() || !$this->config->isDxvk() || !file_exists($this->config->wine('WINEPREFIX'))) {
            return false;
        }

        $oldVersion = $this->version();

        if ('' !== $oldVersion && false === $this->config->isDxvkAutoupdate()) {
            return false;
        }

        $newVersion = $this->versionRemote();

        $this->updateDxvkConfig();

        $dxvk = $this->config->wine('DRIVE_C') . '/dxvk';
        $log  = $this->config->wine('WINEPREFIX') . '/winetricks.log';

        if (file_exists($log)) {
            $winetricks = array_filter(array_map('trim', explode("\n", file_get_contents($log))),
                function ($n) {return !$n && $n !== 'dxvk';});
            file_put_contents($log, implode("\n", $winetricks));
        }

        if ($newVersion !== $oldVersion) {
            (new Wine($this->config, $this->command))->winetricks([$this->config->get('script', 'dxvk_version')?:'dxvk']);
            file_put_contents($dxvk, $newVersion);

            if ($logCallback) {
                $logCallback("DXVK updated to {$newVersion}.");
            }

            return true;
        }

        return false;
    }

    public function updateDxvkConfig()
    {
        if (!($this->config->isDxvk() || $this->config->isD9vk())) {
            return false;
        }

        if (!file_exists($this->config->getDxvkConfFile())) {
            file_put_contents($this->config->getDxvkConfFile(), $this->config->getDefaultDxvkConfig());
        }

        if (!file_exists($this->config->getDxvkConfFile())) {
            return false;
        }

        $currentConfig = trim(file_get_contents($this->config->getDxvkConfFile()));
        $defaultConfig = explode("\n", $this->config->getDefaultDxvkConfig());
        $newConfig     = [];
        $params        = [];

        foreach (explode("\n", $currentConfig) as $line) {
            $line = trim($line);
            if (!Text::startsWith($line, '#')) {
                $item = explode('=', $line);
                $name = trim(reset($item));
                $params[$name] = $line;
            }
        }

        foreach ($defaultConfig as $line) {
            $newConfig[] = $line;

            if (count($params) > 0) {
                $line = trim($line, " \t\n\r\0\x0B#");
                $item = explode('=', $line);
                $name = trim(reset($item));

                if (isset($params[$name]) && $params[$name] !== null) {
                    $newConfig[] = '';
                    $newConfig[] = $params[$name];
                    unset($params[$name]);
                }
            }
        }

        if (count($params) > 0) {
            $newConfig[] = '';
            $newConfig[] = '';
            $newConfig[] = '# Deprecated values.';
            $newConfig[] = '';
            foreach ($params as $line) {
                $newConfig[] = $line;
            }
        }

        $config = trim(implode("\n", $newConfig));

        if (md5($config) !== md5($currentConfig)) {
            file_put_contents($this->config->getDxvkConfFile(), $config);
            return true;
        }

        return false;
    }
}
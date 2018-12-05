<?php

class Network {

    private static $isConnected;

    private $command;
    private $config;

    /**
     * Network constructor.
     * @param Config $config
     * @param Command $command
     */
    public function __construct(Config $config, Command $command)
    {
        $this->command = $command;
        $this->config  = $config;
    }

    public static function isConnected()
    {
        if (self::$isConnected !== null) {
            return self::$isConnected;
        }

        $connected = @fsockopen('8.8.8.8', 53, $errno,$errstr, 5);

        if ($connected) {
            self::$isConnected = true;
            fclose($connected);
        } else {
            self::$isConnected = false;
        }

        return self::$isConnected;
    }

    public function get($url)
    {
        return file_get_contents($url, false, stream_context_create($this->config->getContextOptions()));
    }

    public function getRepo($url)
    {
        return $this->get($this->config->getRepositoryUrl() . $url);
    }
}
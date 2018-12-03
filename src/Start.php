<?php

class Start
{
    private $config;
    private $command;
    private $system;
    private $winePrefix;
    private $gameInfo;
    private $log;
    private $update;
    private $monitor;
    private $buffer;
    private $dataFolder;
    private $wineFolder;
    private $icon;
    private $fs;

    public function __construct()
    {
        if (file_exists(__DIR__ . '/start-tmp')) {
            @unlink(__DIR__ . '/start-tmp');
        }

        $this->config     = new Config();
        $this->command    = new Command($this->config);
        $this->gameInfo   = new GameInfo($this->config, $this->command);
        $this->winePrefix = new WinePrefix($this->config, $this->command);
        $this->system     = new System($this->config, $this->command);
        $this->fs         = new FileSystem($this->config, $this->command);
        $this->update     = new Update($this->config, $this->command);
        $this->monitor    = new Monitor($this->config, $this->command);
        $this->log        = new Logs();
        $this->buffer     = new Buffer();
        $this->icon       = new Icon($this->config, $this->command, $this->system);
        $this->dataFolder = new Mount($this->config, $this->command, $this->config->getDataDir());
        $this->wineFolder = new Mount($this->config, $this->command, $this->config->getWineDir());

        $this->init();
    }

    private function init()
    {
        if (!$this->system->lock()) {
            $this->log->logStart();
            $this->log->log('Application is already running.');
            $this->log->logStop();

            exit(0);
        }

        app($this);
        $this->gameInfo->create();
        $this->winePrefix->create();
        $this->update->init();
        app($this)->start();
//        while (1) sleep(1);
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * @return Command
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @return System
     */
    public function getSystem()
    {
        return $this->system;
    }

    /**
     * @return WinePrefix
     */
    public function getWinePrefix()
    {
        return $this->winePrefix;
    }

    /**
     * @return GameInfo
     */
    public function getGameInfo()
    {
        return $this->gameInfo;
    }

    /**
     * @return Logs
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * @return Update
     */
    public function getUpdate()
    {
        return $this->update;
    }

    /**
     * @return Monitor
     */
    public function getMonitor()
    {
        return $this->monitor;
    }

    /**
     * @return Buffer
     */
    public function getBuffer()
    {
        return $this->buffer;
    }

    /**
     * @return Icon
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @return FileSystem
     */
    public function getFileSystem()
    {
        return $this->fs;
    }
}

pcntl_signal(SIGINT, function ($signal) {
    switch($signal) {
        case SIGINT:
        case SIGKILL:
        case SIGQUIT:
        case SIGTERM:
        case SIGSTOP:
            $scene = app()->getCurrentScene();
            $popup = $scene->addWidget(new PopupInfoWidget($scene->getWindow()));
            $popup
                ->setTitle('Exit')
                ->setText('Wait umount...')
                ->setActive(true)
                ->show();

            exit(0);
    }
}, false);

new Start();
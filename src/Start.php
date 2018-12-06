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
    private $mountes;
    private $icon;
    private $fs;
    private $symlink;
    private $build;
    private $wine;
    private $console;

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
        $this->pack       = new Pack($this->config, $this->command, $this->fs);
        $this->symlink    = new Symlink($this->config, $this->command, $this->fs);
        $this->build      = new Build($this->config, $this->command, $this->system, $this->fs);
        $this->wine       = new Wine($this->config, $this->command);
        $this->console    = new Console($this->config, $this->command, $this->system, $this->log);
        $this->mountes    = [
            new Mount($this->config, $this->command, $this->console, $this->config->getDataDir()),
            new Mount($this->config, $this->command, $this->console, $this->config->getWineDir()),
        ];

        $this->init();
    }

    private function init()
    {
        $this->console->lock();

        app($this);

        $this->gameInfo->create();
        $this->winePrefix->create();
        $this->update->init();

        if (!$this->system->checkPhp() || (!$this->gameInfo->isCreated() && !$this->winePrefix->isCreated())) {
            $this->console->init();
        }

        if (!$this->system->checkPhp()) {
            return;
        }

        if ($this->gameInfo->isCreated() || $this->winePrefix->isCreated() || $this->console->isGui()) {
            app('gui');
            app($this)->start();
        }
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param Config $config
     */
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

    /**
     * @return array[Mount]
     */
    public function getMountes()
    {
        return $this->mountes;
    }

    /**
     * @return Pack
     */
    public function getPack()
    {
        return $this->pack;
    }

    /**
     * @return Symlink
     */
    public function getSymlink()
    {
        return $this->symlink;
    }

    /**
     * @return Build
     */
    public function getBuild()
    {
        return $this->build;
    }

    /**
     * @return Wine
     */
    public function getWine()
    {
        return $this->wine;
    }


    /**
     * @return Console
     */
    public function getConsole()
    {
        return $this->console;
    }
}

pcntl_signal(SIGINT, function ($signal) {
    switch($signal) {
        case SIGINT:
        case SIGKILL:
        case SIGQUIT:
        case SIGTERM:
        case SIGSTOP:
            if (app('start')->getSystem()->checkPhp()) {
                $scene = app()->getCurrentScene();
                $popup = $scene->addWidget(new PopupInfoWidget($scene->getWindow()));
                $popup
                    ->setTitle('Exit')
                    ->setText('Wait umount...')
                    ->setActive(true)
                    ->show();
            }

            exit(0);
    }
}, false);

new Start();
<?php

abstract class AbstractPlugin
{
    /** @var Event */
    protected $event;
    /** @var Config */
    protected $config;
    /** @var Command */
    protected $command;
    /** @var FileSystem */
    protected $fs;
    /** @var System */
    protected $system;
    /** @var Replaces */
    protected $replaces;
    /** @var Monitor */
    protected $monitor;
    /** @var string */
    public $title = '';
    /** @var string */
    public $description = '';

    /**
     * AbstractPlugin constructor.
     * @param Event $event
     * @param Config $config
     * @param Command $command
     * @param FileSystem $fs
     * @param System $system
     * @param Replaces $replaces
     * @param Monitor $monitor
     */
    public function __construct(Event $event, Config $config, Command $command, FileSystem $fs, System $system, Replaces $replaces, Monitor $monitor)
    {
        $this->event    = $event;
        $this->config   = $config;
        $this->command  = $command;
        $this->fs       = $fs;
        $this->system   = $system;
        $this->replaces = $replaces;
        $this->monitor  = $monitor;

        $this->init();
    }

    abstract public function init();

    public function setConfig(Config $config)
    {
        $this->config = $config;
    }
}
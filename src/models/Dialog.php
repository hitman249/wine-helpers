<?php

class Dialog {

    private $config;
    private $command;

    /**
     * Dialog constructor.
     * @param Config $config
     * @param Command $command
     */
    public function __construct(Config $config, Command $command)
    {
        $this->config  = $config;
        $this->command = $command;
    }
}
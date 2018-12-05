<?php

class Console {

    private $config;
    private $command;

    /**
     * Console constructor.
     * @param Config $config
     * @param Command $command
     */
    public function __construct(Config $config, Command $command)
    {
        $this->config  = $config;
        $this->command = $command;
    }
}
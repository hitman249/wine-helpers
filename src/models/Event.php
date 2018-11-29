<?php

class Event {

    private $command;
    private $config;

    /**
     * Event constructor.
     * @param Config $config
     * @param Command $command
     */
    public function __construct(Config $config, Command $command)
    {
        $this->command = $command;
        $this->config  = $config;
    }

    public function createPrefix($type = 'after_create_prefix')
    {
        if (!file_exists($this->config->wine('WINEPREFIX')) || !file_exists($this->config->getHooksDir())) {
            return [];
        }

        $result = [];

        if ($this->config->get('hooks', $type)) {
            foreach ((array)$this->config->get('hooks', $type) as $hookCmd) {
                $hookCmd = trim($hookCmd);
                if (!$hookCmd) {
                    continue;
                }

                $trimHook = trim($hookCmd, '&');

                if (file_exists($this->config->getHooksDir() . "/{$trimHook}")) {
                    $result[] = "Run {$trimHook}";
                    $result[] = $this->command->run('cd "' . $this->config->getHooksDir() . '"; chmod +x ' . $trimHook . "; ./{$hookCmd}");
                }
            }
        }

        return $result;
    }

    public function beforeRun()
    {
        $this->createPrefix('before_run_game');
    }

    public function afterExit()
    {
        $this->createPrefix('after_exit_game');
    }

    public function gpu()
    {
        $gpu = (new System($this->config, $this->command))->getTypeGPU();

        if (!file_exists($this->config->wine('WINEPREFIX')) || !file_exists($this->config->getHooksGpuDir()) || !$gpu) {
            return [];
        }

        $result = [];

        if ($this->config->get('hooks', "gpu_{$gpu}")) {
            $hooks = (array)$this->config->get('hooks', "gpu_{$gpu}");

            foreach ($hooks as $hook) {
                $hookCmd = trim($hook);
                if (!$hookCmd) {
                    continue;
                }

                $trimHook = trim($hookCmd, '&');

                if (file_exists($this->config->getHooksDir() . "/{$trimHook}")) {
                    $result[] = "Run {$trimHook}";
                    $result[] = $this->command->run('cd "' . $this->config->getHooksDir() . '"; chmod +x ' . Text::quoteArgs($trimHook) . "; ./{$hookCmd}", true);
                }
            }
        }

        return $result;
    }
}
<?php

class Event
{
    const EVENT_BEFORE_START_GAME   = 'before-start';
    const EVENT_AFTER_START_GAME    = 'after-start';
    const EVENT_AFTER_CREATE_PREFIX = 'after-create-prefix';

    private $command;
    private $config;

    protected $events;

    /**
     * Event constructor.
     * @param Config $config
     * @param Command $command
     */
    public function __construct(Config $config, Command $command)
    {
        $this->command = $command;
        $this->config  = $config;

        if (null === $this->events) {
            $this->events = [];
        }
    }

    public function on($event, $callbackOrNamespaceStaticFunc)
    {
        if (!isset($this->events[$event])) {
            $this->events[$event] = [];
        }

        $this->events[$event][] = $callbackOrNamespaceStaticFunc;
    }

    public function fireEvent($event)
    {
        if (empty($this->events[$event])) {
            return;
        }

        foreach ($this->events[$event] as $item) {
            $item();
        }
    }

    public function createPrefix($type = 'after_create_prefix')
    {
        if (!file_exists($this->config->wine('WINEPREFIX'))) {
            return [];
        }

        if (file_exists($this->config->getHooksDir())) {
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
        }

        if ('after_create_prefix' === $type) {
            $this->fireEvent(self::EVENT_AFTER_CREATE_PREFIX);
        } elseif ('before_run_game' === $type) {
            $this->fireEvent(self::EVENT_BEFORE_START_GAME);
        } elseif ('after_exit_game' === $type) {
            $this->fireEvent(self::EVENT_AFTER_START_GAME);
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
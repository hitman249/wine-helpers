<?php

class Console
{
    private $config;
    private $command;
    private $system;
    private $arguments;
    private $log;
    private $gui = false;

    /**
     * Console constructor.
     * @param Config $config
     * @param Command $command
     * @param System $system
     * @param Logs $log
     */
    public function __construct(Config $config, Command $command, System $system, Logs $log)
    {
        $this->config  = $config;
        $this->command = $command;
        $this->system  = $system;
        $this->log     = $log;

        global $argv;
        $this->arguments = array_splice($argv, 1);
    }

    public function isGui()
    {
        return trim(reset($this->arguments)) === 'gui' || $this->gui;
    }

    public function isHelp()
    {
        return in_array(trim(reset($this->arguments)), ['help', '-help', '--help', '-h']);
    }

    public function isWinetricks()
    {
        return trim(reset($this->arguments)) === 'winetricks';
    }

    public function isKill()
    {
        return trim(reset($this->arguments)) === 'kill';
    }

    public function isWine()
    {
        return trim(reset($this->arguments)) === 'wine';
    }

    public function wine($args)
    {
        $config = clone $this->config;
        $config->set('wine', 'WINEDEBUG', '');
        $cmd = Text::quoteArgs($args);
        (new Command($config))->run(Text::quoteArgs($config->wine('WINE')) . " {$cmd}", null, true);
    }

    public function lock()
    {
        static $lock;

        if (null === $lock) {

            $lock = (!$this->isKill() && !$this->isWinetricks() && !$this->isHelp() && !$this->isWine());

            if ($lock && !$this->system->lock()) {
                $this->log->logStart();
                $this->log->log('Application is already running.');
                $this->log->logStop();

                exit(0);
            }
        }

        return $lock;
    }

    public function init()
    {
        if (!$this->arguments) {

            (new Monitor($this->config, $this->command))->resolutionsRestore();

            /** @var Config $config */
            $config = app('start')->getConfig();

            $configs = $config->findConfigsPath();

            $starts = [];

            foreach ($configs as $i => $path) {
                if ($config->getConfigFile() === $path && count($configs) === 1) {
                    $starts[] = ['name' => $config->getGameTitle(), 'config' => $config];
                } else {
                    $cfg = new Config($path);
                    $starts[] = ['name' => $cfg->getGameTitle(), 'config' => $cfg];
                }
            }

            $title = 'Run';

            if ($this->system->isCyrillic()) {
                $title = 'Запустить';
            }

            $item = (new Dialog())
                ->columns(['name' => $title])
                ->items($starts)
                ->size(400, 300)
                ->get();

            if (!$item) {
                exit(0);
            }

            /** @var Config $config */
            $config = $item['config'];

            $task = new Task($config);
            $task
                ->logName($config->getGameTitle())
                ->game()
                ->run();
        }

        if ($this->isKill()) {
            (new Wine($this->config, $this->command))->down();
            exit(0);
        }

        if ($this->isWine()) {
            $this->wine(array_splice($this->arguments, 1));
            exit(0);
        }

        if ($this->isWinetricks()) {
            (new Wine($this->config, $this->command))->winetricks(array_splice($this->arguments, 1), true);
            exit(0);
        }

        if (!$this->isGui() && ($this->isHelp() || $this->arguments)) {
            $help = [
                'Help:',
                './start             - Run game.',
                './start gui         - Graphical user interface.',
                './start kill        - Kill this instance Wine.',
                './start winetricks  - Winetricks install d3dx9 (./start winetricks d3dx9).',
                './start wine        - Get Wine Instance.',
                './start help',
            ];
            $this->log->log(implode("\n", $help));
            exit(0);
        }

        (new Monitor($this->config, $this->command))->resolutionsRestore();
    }
}
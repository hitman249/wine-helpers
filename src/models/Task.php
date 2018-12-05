<?php

class Task
{
    private $command;
    private $config;
    private $logfile;
    private $cmd;
    private $monitor;
    private $event;
    private $fs;
    private $fps;
    private $fpsCmd;
    private $system;
    private $update;

    /**
     * Task constructor.
     * @param Config $config
     * @param Wine $wine
     */
    public function __construct(Config $config)
    {
        $this->config  = clone $config;
        $this->command = new Command($this->config);
        $this->wine    = new Wine($this->config, $this->command);
        $this->monitor = new Monitor($this->config, $this->command);
        $this->event   = new Event($this->config, $this->command);
        $this->fs      = new FileSystem($this->config, $this->command);
        $this->system  = new System($this->config, $this->command);
        $this->update  = new Update($this->config, $this->command);
    }

    public function logName($prefix)
    {
        $this->logfile = "{$prefix}.log";
        return $this;
    }

    public function debug()
    {
        $this->config->set('wine', 'WINEDEBUG', '');
        return $this;
    }

    public function fps()
    {
        $this->fps = true;

        $root = $this->config->getRootDir();

        if (!$this->config->isDxvk()) {
            $mesa = $this->system->getMesaVersion();

            if ($mesa) {
                $this->config->set('export', 'GALLIUM_HUD', 'simple,fps');
            } else {
                $this->update->downloadOsd();
                $font = $this->system->getFont();
                $font = $font ? "--font=\"{$font}\"" : '';
                $add = [
                    ' 2>&1',
                    'tee /dev/stderr',
                    'sed -u -n -e \'/trace/ s/.*approx //p\'',
                    "\"{$root}/osd\" --lines=1 {$font} --color=yellow",
                ];
                $this->fpsCmd = implode(' | ', $add);
                $this->config->set('export', 'WINEDEBUG', '-all,fps');
            }
        } elseif (!$this->config->get('export', 'DXVK_HUD')) {
            $this->config->set('export', 'DXVK_HUD', 'fps,devinfo,memory');
        }

        return $this;
    }

    public function cmd($cmd)
    {
        $this->cmd = $cmd;
        return $this;
    }

    public function run()
    {
        $this->beforeRun();

        $logging = '';

        if ($this->logfile) {
            $logging = $this->fpsCmd ?: '&> ' . Text::quoteArgs($this->config->getLogsDir() . "/{$this->logfile}");
        }

        $this->command->run("{$this->cmd} {$logging}");

        $this->afterExit();
    }

    private function beforeRun()
    {
        $scene = app()->getCurrentScene();
        $popup = $scene->addWidget(new PopupInfoWidget($scene->getWindow()));
        $popup
            ->setTitle('Running')
            ->setText([
                'Application is running...',
                $this->fpsCmd ? '' : 'See log ./' . $this->fs->relativePath($this->config->getLogsDir() . "/{$this->logfile}"),
            ])
            ->setActive(true)
            ->show();

        $this->monitor->resolutionsSave();
        $this->event->beforeRun();
    }

    private function afterExit()
    {
        $this->event->afterExit();
        $this->monitor->resolutionsRestore();
        app()->close();
    }
}
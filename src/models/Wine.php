<?php

class Wine {

    private $command;
    private $config;
    private $version;
    private $missingLibs;

    /**
     * Wine constructor.
     * @param Config $config
     * @param Command $command
     */
    public function __construct(Config $config, Command $command)
    {
        $this->command = $command;
        $this->config  = $config;
    }

    public function boot()
    {
        $this->command->run(Text::quoteArgs($this->config->wine('WINEBOOT')) . ' && ' . Text::quoteArgs($this->config->wine('WINESERVER')) . ' -w');
    }

    public function down()
    {
        $this->command->run(Text::quoteArgs($this->config->wine('WINESERVER')) . ' -k');
    }

    public function run($args)
    {
        $cmd = Text::quoteArgs($args);

        $result = $this->command->run(Text::quoteArgs($this->config->wine('WINE')) . " {$cmd}");

        if ($this->config->wine('WINEARCH') === 'win64') {
            $result .= $this->command->run(Text::quoteArgs($this->config->wine('WINE64')) . " {$cmd}");
        }

        return $result;
    }

    public function fm($args)
    {
        $config = clone $this->config;
        $config->set('wine', 'WINEDEBUG', '');
        $cmd = Text::quoteArgs($args);
        $logFile = $this->config->getLogsDir() . '/filemanager.log';

        return app('start')->getPatch()->create(function () use ($config, $cmd, $logFile) {
            return (new Command($config))->run(Text::quoteArgs($this->config->wine('WINEFILE')) . " {$cmd}", $logFile);
        });
    }

    public function cfg($args)
    {
        $cmd = Text::quoteArgs($args);

        return $this->command->run(Text::quoteArgs($this->config->wine('WINECFG')) . " {$cmd}");
    }

    public function reg($args)
    {
        $cmd = Text::quoteArgs($args);
        $result = $this->command->run(Text::quoteArgs($this->config->wine('REGEDIT')) . " {$cmd}");

        if ($this->config->wine('WINEARCH') === 'win64') {
            $result .= $this->command->run(Text::quoteArgs($this->config->wine('REGEDIT64')) . " {$cmd}");
        }

        return $result;
    }

    public function regsvr32($args)
    {
        $this->run(array_merge(['regsvr32'], $args));
    }

    public function winetricks($args, $output = false)
    {
        (new Update($this->config, $this->command))->downloadWinetricks();

        if ($args && file_exists($this->config->getRootDir() . '/winetricks')) {

            $config = clone $this->config;
            $config->set('wine', 'WINEDEBUG', '');
            $cmd = Text::quoteArgs($args);
            $title = implode('-', $args);
            $title = mb_strlen($title) > 50 ? mb_substr($title, 0, 48) . '..' : $title;
            $logFile = $this->config->getLogsDir() . "/winetricks-{$title}.log";
            return app('start')->getPatch()->create(function () use (&$config, $cmd, $logFile, $output) {
                return (new Command($config))->run(Text::quoteArgs($this->config->getRootDir() . '/winetricks') . " {$cmd}", $logFile, $output);
            });
        }

        return '';
    }

    public function checkSystemWine()
    {
        return (bool)trim($this->command->run('command -v "wine"'));
    }

    public function checkWine()
    {
        return (bool)trim($this->command->run('command -v ' . Text::quoteArgs($this->config->wine('WINE'))));
    }

    public function version()
    {
        if (null === $this->version) {
            $this->version = trim($this->command->run(Text::quoteArgs($this->config->wine('WINE')) . ' --version'));
        }

        return $this->version;
    }

    public function isUsedSystemWine()
    {
        return !file_exists($this->config->getRootDir() . '/wine/bin/wine')
            || version_compare((new System($this->config, $this->command))->getGlibcVersion(), '2.23', '<');
    }

    public function getMissingLibs()
    {
        if (null === $this->missingLibs) {
            $help = $this->command->run(Text::quoteArgs($this->config->wine('WINE')) . ' --help');

            if (strpos($help, '--check-libs') === false) {
                $this->missingLibs = [];
                return $this->missingLibs;
            }

            $this->missingLibs = $this->command->run(Text::quoteArgs($this->config->wine('WINE')) . ' --check-libs');
            $this->missingLibs = array_filter(
                array_map('trim', explode("\n", $this->missingLibs)),
                function ($line) {
                    if (!$line) {
                        return false;
                    }

                    list($left, $right) = array_map(
                        function ($s) {return trim($s, " \t\n\r\0\x0B.");},
                        explode(':', $line)
                    );

                    return strpos($right, '.') === false;
                }
            );
        }

        return $this->missingLibs;
    }
}
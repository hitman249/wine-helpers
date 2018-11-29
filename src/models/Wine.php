<?php

class Wine {

    private $command;
    private $config;

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

        return (new Command($config))->run(Text::quoteArgs($this->config->wine('WINEFILE')) . " {$cmd}");
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

    public function winetricks($args)
    {
        (new Update($this->config, $this->command))->downloadWinetricks();

        if (file_exists($this->config->getRootDir() . '/winetricks')) {
            $config = clone $this->config;
            $config->set('wine', 'WINEDEBUG', '');
            $cmd = Text::quoteArgs($args);

            return (new Command($config))->run(Text::quoteArgs($this->config->getRootDir() . '/winetricks') . " {$cmd}");
        }

        return '';
    }

    public function checkSystemWine()
    {
        return (bool)trim($this->command->run('which "wine"'));
    }

    public function checkWine()
    {
        return (bool)trim($this->command->run('which ' . Text::quoteArgs($this->config->wine('WINE'))));
    }

    public function version()
    {
        static $version;

        if (null === $version) {
            $version = trim($this->command->run(Text::quoteArgs($this->config->wine('WINE')) . ' --version'));
        }

        return $version;
    }

    public function getMissingLibs()
    {
        static $result;

        if (null === $result) {
            $help = $this->command->run(Text::quoteArgs($this->config->wine('WINE')) . ' --help');

            if (strpos($help, '--check-libs') === false) {
                $result = [];
                return $result;
            }

            $result = $this->command->run(Text::quoteArgs($this->config->wine('WINE')) . ' --check-libs');
            $result = array_filter(
                array_map('trim', explode("\n", $result)),
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

        return $result;
    }
}
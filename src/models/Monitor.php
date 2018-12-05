<?php

class Monitor {

    private $config;
    private $command;
    private $xrandr;
    private $monitors;
    private $jsonPath;

    /**
     * Monitor constructor.
     */
    public function __construct(Config $config, Command $command)
    {
        $this->command  = $command;
        $this->config   = $config;
        $this->jsonPath = $this->config->getRootDir() . '/resolutions.json';
    }

    public function isXrandr()
    {
        if (null === $this->xrandr) {
            $this->xrandr = (bool)trim($this->command->run("which xrandr"));
        }

        return $this->xrandr;
    }

    public function resolutions($reload = false)
    {
        if (!$this->isXrandr()) {
            return [];
        }

        if (false === $reload && null !== $this->monitors) {
            return $this->monitors;
        }

        $head = '/^(.*) connected( | primary )([0-9]{3,4}x[0-9]{3,4}).*\n*/m';
        $dump = $this->command->run('xrandr --verbose');
        $array = explode("\n", $dump);
        $monitors = [];

        preg_match_all($head, $dump, $matches);

        foreach ($matches[0] as $i => $_line) {
            $monitors[$matches[1][$i]] = [
                'output' => $matches[1][$i],
                'resolution' => $matches[3][$i],
            ];

            $inner = false;
            foreach ($array as $line) {
                if (!$line || !$_line) {
                    continue;
                }
                if ($inner === false && strpos($_line, $line) !== false) {
                    $inner = true;
                    $monitors[$matches[1][$i]]['default'] = strpos($line, 'primary') !== false;
                } elseif ($inner) {
                    if (strpos($line, 'connected') !== false || strpos($line, 'disconnected') !== false) {
                        $inner = false;
                    } else {
                        if (isset($monitors[$matches[1][$i]]['brightness'], $monitors[$matches[1][$i]]['gamma'])) {
                            $inner = false;
                            break;
                        }
                        if (strpos($line, 'Brightness:') !== false) {
                            $value = trim(str_replace('Brightness:', '', $line));
                            $monitors[$matches[1][$i]]['brightness'] = $value;
                        }
                        if (strpos($line, 'Gamma:') !== false) {
                            $value = trim(str_replace('Gamma:', '', $line));
                            $monitors[$matches[1][$i]]['gamma'] = $value;
                        }
                    }
                }
            }
        }

        $this->monitors = $monitors;

        return $this->monitors;
    }

    public function getDefaultMonitor()
    {
        $monitors = $this->resolutions();

        foreach ($monitors as $monitor) {
            if ($monitor['default']) {
                return $monitor;
            }
        }

        return [];
    }

    public function resolutionsSave()
    {
        file_put_contents($this->jsonPath, json_encode($this->resolutions(true), JSON_PRETTY_PRINT));
    }

    public function resolutionLoad()
    {
        if (file_exists($this->jsonPath)) {
            return json_decode(file_get_contents($this->jsonPath), true);
        }

        return null;
    }

    public function resolutionsRestore()
    {
        if (!$this->isXrandr()) {
            return [];
        }

        $result   = [];
        $monitors = $this->resolutions(true);

        foreach ($this->resolutionLoad()?:[] as $output => $params) {
            if ($monitors[$output]) {
                if ($params['gamma'] !== $monitors[$output]['gamma']) {
                    $this->command->run(Text::quoteArgs($this->config->wine('WINESERVER')) . " -w && xrandr --output {$output} --gamma {$params['gamma']}");
                    $result[] = "Revert gamma, output {$output}, gamma {$monitors[$output]['gamma']} > {$params['gamma']}.";
                }
                if ($params['brightness'] !== $monitors[$output]['brightness']) {
                    $this->command->run(Text::quoteArgs($this->config->wine('WINESERVER')) . " -w && xrandr --output {$output} --brightness {$params['brightness']}");
                    $result[] = "Revert brightness, output {$output}, brightness {$monitors[$output]['brightness']} > {$params['brightness']}.";
                }
                if ($params['resolution'] !== $monitors[$output]['resolution']) {
                    $this->command->run(Text::quoteArgs($this->config->wine('WINESERVER')) . " -w && xrandr --output {$output} --mode {$params['resolution']}");
                    $result[] = "Revert resolution, output {$output}, resolution {$monitors[$output]['resolution']} > {$params['resolution']}.";
                }
            }
        }

        if (file_exists($this->jsonPath)) {
            @unlink($this->jsonPath);
        }

        return $result;
    }
}
<?php

class Update
{
    private $version = '0.85';
    private $command;
    private $config;
    private $network;
    private $system;

    /**
     * Update constructor.
     * @param Config $config
     * @param Command $command
     */
    public function __construct(Config $config, Command $command)
    {
        $this->command = $command;
        $this->config  = $config;
        $this->network = new Network($config, $command);
        $this->system  = new System($config, $command);
    }

    public function version()
    {
        return $this->version;
    }

    public function getUrl()
    {
        return 'https://github.com/hitman249/wine-helpers';
    }

    public function init()
    {
        /**
         * Autoupdate script
         */
        if ($this->autoupdate()) {
            $this->restart();
        }


        /**
         * Autoupdate dxvk
         */
        (new DXVK($this->config, $this->command, $this->network))->update();


        /**
         * Autoupdate d9vk
         */
        (new D9VK($this->config, $this->command, $this->network, app('start')->getFileSystem(), app('start')->getWine()))->update();


        /**
         * README.md
         */
        $this->updateReadme();


        $restart = $this->config->getRootDir() . '/restart';

        if (file_exists($restart)) {
            unlink($restart);
        }
    }

    public function updateReadme($create = false, $update = false)
    {
        if ($create === false) {

            if (!file_exists($this->config->getRootDir() . '/README.md')) {
                return false;
            }

            $path = $this->config->wine('DRIVE_C') . '/readme';

            if (!file_exists($path)) {
                if ($update === true) {
                    file_put_contents($path, ' ');
                    return false;
                }
                if ($update === false) {
                    return false;
                }
            }

            if ($update === true) {
                return false;
            }

            if (file_exists($path)) {
                unlink($path);
            } else {
                return false;
            }

        }

        if (Network::isConnected()) {
            if (file_exists($this->config->wine('DRIVE_C'))) {
                $readme = $this->network->get($this->config->getRepositoryUrl() . '/README.md');

                if ($readme) {
                    /**
                     * README.md
                     */
                    file_put_contents($this->config->getRootDir() . '/README.md', $readme);

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param Config|null $config
     * @return bool
     */
    public function updateConfig($config = null)
    {
        if (null === $config) {
            $config = $this->config;
        }

        if (!file_exists($config->getConfigFile())) {
            return false;
        }

        $result = [];

        $current          = $config->getConfig();
        $currentText      = file_get_contents($config->getConfigFile());
        $defaultText      = $config->getDefaultConfig();
        $defaultTextArray = explode("\n", $defaultText);

        $section = null;
        $space = null;
        foreach ($defaultTextArray as $line) {
            $line = trim($line);

            if (!$line) {
                $result[] = '';
                continue;
            }
            if (Text::startsWith($line, '[') && Text::endsWith($line, ']')) {

                if ($section !== null) {
                    if ($space === null) {
                        $space = true;
                        $result[] = '';
                    }

                    if ($current[$section]) {
                        foreach ($current[$section]?:[] as $key => $value) {
                            if (is_array($value)) {
                                foreach ($value?:[] as $k => $v) {
                                    $v = is_numeric($v) && $v !== null && $v !== '' ? $v : "\"{$v}\"";
                                    if (is_numeric($k)) {
                                        $result[] = "{$key}[] = {$v}";
                                    } else {
                                        $result[] = "{$key}[{$k}] = {$v}";
                                    }
                                }
                            } else {
                                $value = is_numeric($value) && $value !== null && $value !== '' ? $value : "\"{$value}\"";
                                $result[] = "{$key} = {$value}";
                            }
                        }

                        $result[] = '';
                    }
                }

                $space = null;
                $result[] = $line;
                $section  = trim(str_replace(['[',']'], '', $line));
                continue;
            }
            if (Text::startsWith($line, ';')) {
                $result[] = $line;
                continue;
            }

            if ($section !== null) {

                list($key, $value) = array_map(function ($n) { return trim($n, " \t\n\r\0\x0B\"'");}, explode('=', $line));

                if (Text::endsWith($key, ']')) {

                } else {
                    if (!isset($current[$section][$key])) {
                        $result[] = $line;
                    } else {
                        $value = $current[$section][$key];
                        $value = is_numeric($value) && $value !== null && $value !== '' ? $value : "\"{$value}\"";
                        $result[] = "{$key} = {$value}";
                        unset($current[$section][$key]);
                    }
                }
            }
        }

        if ($section !== null) {
            $result[] = '';

            foreach ($current[$section]?:[] as $key => $value) {
                if (is_array($value)) {
                    foreach ($value?:[] as $k => $v) {
                        $v = is_numeric($v) && $v !== null && $v !== '' ? $v : "\"{$v}\"";
                        if (is_numeric($k)) {
                            $result[] = "{$key}[] = {$v}";
                        } else {
                            $result[] = "{$key}[{$k}] = {$v}";
                        }
                    }
                } else {
                    $value = is_numeric($value) && $value !== null && $value !== '' ? $value : "\"{$value}\"";
                    $result[] = "{$key} = {$value}";
                }
            }

            $result[] = '';
        }

        $newConfig = implode("\n", $result);

        if (md5($currentText) !== md5($newConfig)) {
            file_put_contents($config->getConfigFile(), $newConfig);
            app('start')->getConfig()->reload();
            return true;
        }

        return false;
    }

    public function versionRemote()
    {
        static $version;

        if (null === $version) {
            $version = trim($this->network->get($this->config->getRepositoryUrl() . '/RELEASE'));
        }

        return $version;
    }

    public function autoupdate()
    {
        if ($this->config->isScriptAutoupdate() && Network::isConnected()) {
            if ($this->versionRemote() !== $this->version()) {
                return $this->update();
            }
        }

        return false;
    }

    public function update()
    {
        $newStart = $this->network->get($this->config->getRepositoryUrl() . '/start');

        if ($newStart) {
            file_put_contents($this->config->getRootDir() . '/start', $newStart);
            $this->command->run('chmod +x ' . Text::quoteArgs($this->config->getRootDir() . '/start'));

            /**
             * README.md
             */
            $this->updateReadme(false, true);

            return true;
        }

        return false;
    }

    public function downloadWinetricks()
    {
        $filePath = $this->config->getRootDir() . '/winetricks';
        $isDelete = false;

        if (file_exists($filePath)) {
            $createAt  = filectime($filePath);
            $currentAt = time();

            if (($currentAt - $createAt) > 86400) {
                $isDelete = true;
            }
        }

        if ($isDelete || !file_exists($filePath)) {
            $winetricks = $this->network
                ->get('https://raw.githubusercontent.com/Winetricks/winetricks/master/src/winetricks');

            if ($winetricks) {
                file_put_contents($filePath, $winetricks);
                $this->command->run("chmod +x \"{$filePath}\"");

                return true;
            }
        }

        return false;
    }

    public function downloadSquashfuse()
    {
        $filePath = $this->config->getRootDir() . '/squashfuse';

        if (!file_exists($filePath)) {

            $squashfuse = $this->network->getRepo('/squashfuse');

            if ($squashfuse) {
                file_put_contents($filePath, $squashfuse);
                $this->command->run("chmod +x \"{$filePath}\"");

                return true;
            }
        }

        return false;
    }

    public function downloadFusezip()
    {
        $filePath = $this->config->getRootDir() . '/fuse-zip';

        if (!file_exists($filePath)) {

            $fusezip = $this->network->getRepo('/fuse-zip');

            if ($fusezip) {
                file_put_contents($filePath, $fusezip);
                $this->command->run("chmod +x \"{$filePath}\"");

                return true;
            }
        }

        return false;
    }

    public function downloadHwprobe()
    {
        $filePath = $this->config->getRootDir() . '/hw-probe';

        if (!file_exists($filePath)) {

            $hwprobe = $this->network->getRepo('/hw-probe');

            if ($hwprobe) {
                file_put_contents($filePath, $hwprobe);
                $this->command->run("chmod +x \"{$filePath}\"");

                return true;
            }
        }

        return false;
    }

    public function downloadOsd()
    {
        $filePath = $this->config->getRootDir() . '/osd';

        if (!file_exists($filePath)) {

            $osd = $this->network->getRepo('/osd');

            if ($osd) {
                file_put_contents($filePath, $osd);
                $this->command->run("chmod +x \"{$filePath}\"");

                return true;
            }
        }

        return false;
    }

    public function updatePhp()
    {
        if ($this->system->checkPhp()) {
            return false;
        }

        $filePath = $this->config->getRootDir() . '/php';

        $php = $this->network->getRepo('/php');

        if ($php) {
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            file_put_contents($filePath, $php);
            $this->command->run("chmod +x \"{$filePath}\"");

            return true;
        }

        return false;
    }

    public function restart()
    {
        $restart = $this->config->getRootDir() . '/restart';

        if (!file_exists($restart)) {
            file_put_contents($restart, ' ');
            exit(0);
        }
    }
}
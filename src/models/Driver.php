<?php

class Driver
{
    private $config;
    private $command;
    private $system;

    /**
     * Driver constructor.
     * @param Config $config
     * @param Command $command
     * @param System $system
     */
    public function __construct(Config $config, Command $command, System $system)
    {
        $this->config  = $config;
        $this->command = $command;
        $this->system  = $system;
    }

    public function getNvidia()
    {
        static $result;

        if (null !== $result) {
            return $result;
        }

        $proc = '/proc/driver/nvidia/version';

        if (file_exists($proc)) {
            $text = file_get_contents($proc);
            if ($text) {
                $text = array_map('trim', explode('Module', $text));
                if (!empty($text[1])) {
                    $version = array_map('trim', explode(' ', trim($text[1])));
                    $version = reset($version);
                    $result  = ['vendor' => 'nvidia', 'driver' => 'nvidia', 'version' => $version];

                    return $result;
                }
            }
        }

        if (trim($this->command->run('command -v nvidia-smi'))) {
            $text = trim($this->command->run('nvidia-smi --query-gpu=driver_version --format=csv,noheader'));

            if (!empty($text) && mb_strlen($text) < 10) {
                $result = ['vendor' => 'nvidia', 'driver' => 'nvidia', 'version' => $text];
                return $result;
            }
        }

        $text = trim($this->command->run('modinfo nvidia | grep -E "^version:"'));

        if ($text && stripos($text, 'modinfo') === false) {
            $text = array_map('trim', explode('ersion:', $text));

            if (!empty($text[1])) {
                $version = array_map('trim', explode(' ', trim($text[1])));
                $version = reset($version);
                $result  = ['vendor' => 'nvidia', 'driver' => 'nvidia', 'version' => $version];

                return $result;
            }
        }

        if (trim($this->command->run('lsmod | grep nouveau'))) {
            $result = ['vendor' => 'nvidia', 'driver' => 'nouveau', 'version' => ''];
            return $result;
        }

        if (null === $result) {
            $result = [];
        }

        return $result;
    }

    public function getAmd()
    {
        static $result;

        if (null !== $result) {
            return $result;
        }

        if (trim($this->command->run('lsmod | grep radeon'))) {
            $result = ['vendor' => 'amd', 'driver' => 'radeon', 'version' => '', 'mesa' => $this->system->getMesaVersion()];
            return $result;
        }

        $text = trim($this->command->run('modinfo amdgpu | grep -E "^version:"'));

        if ($text && stripos($text, 'modinfo') === false) {
            $text = array_map('trim', explode('ersion:', $text));

            if (!empty($text[1])) {
                $version = array_map('trim', explode(' ', trim($text[1])));
                $version = reset($version);
                $result  = ['vendor' => 'amd', 'driver' => 'amdgpu-pro', 'version' => $version];

                return $result;
            }
        }

        if (trim($this->command->run('lsmod | grep amdgpu'))) {
            $result = ['vendor' => 'amd', 'driver' => 'amdgpu', 'version' => '', 'mesa' => $this->system->getMesaVersion()];
            return $result;
        }

        return $result;
    }

    public function getIntel()
    {
        static $result;

        if (null !== $result) {
            return $result;
        }

        if ((bool)trim($this->command->run('glxinfo | grep "Intel"'))) {
            $result = ['vendor' => 'intel', 'driver' => 'intel', 'version' => '', 'mesa' => $this->system->getMesaVersion()];
            return $result;
        }

        return $result;
    }

    public function getVersion()
    {
        $driver = $this->getNvidia();

        if ($driver) {
            return $driver;
        }

        $driver = $this->getAmd();

        if ($driver) {
            return $driver;
        }

        $driver = $this->getIntel();

        if ($driver) {
            return $driver;
        }

        return $driver;
    }

    public function isGalliumNineSupport()
    {
        return $this->getAmd() || $this->getIntel();
    }
}
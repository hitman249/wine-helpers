<?php

class Replaces
{
    private $config;
    private $command;
    private $fs;
    private $system;
    private $monitor;
    private $finds;
    private $replaces;
    private $width;
    private $height;
    private $userName;

    /**
     * Replaces constructor.
     * @param Config $config
     * @param Command $command
     * @param FileSystem $fs
     * @param System $system
     * @param Monitor $monitor
     */
    public function __construct(Config $config, Command $command, FileSystem $fs, System $system, Monitor $monitor)
    {
        $this->command  = $command;
        $this->config   = $config;
        $this->fs       = $fs;
        $this->system   = $system;
        $this->monitor  = $monitor;
        $this->finds    = [
            '{WIDTH}',
            '{HEIGHT}',
            '{USER}',
            '{DOSDEVICES}',
            '{PREFIX}',
            '{DRIVE_C}',
            '{ROOT_DIR}',
        ];
    }

    private function init()
    {
        if (null === $this->replaces) {

            $this->userName = $this->system->getUserName();

            $this->width  = '';
            $this->height = '';

            foreach ($this->monitor->resolutions() as $output => $monitor) {
                if (!$this->width || !$this->height) {
                    list($w, $h) = explode('x', $monitor['resolution']);
                    $this->width  = $w;
                    $this->height = $h;
                }
                if ($monitor['default']) {
                    list($w, $h) = explode('x', $monitor['resolution']);
                    $this->width  = $w;
                    $this->height = $h;
                }
            }

            $this->replaces = [
                $this->width,
                $this->height,
                $this->userName,
                $this->config->getPrefixDosdeviceDir(),
                $this->config->getPrefixFolder(),
                $this->config->getPrefixDriveC(),
                $this->config->getRootDir(),
            ];
        }
    }

    public function replaceByFile($path, $backup = false)
    {
        $this->init();

        if ($backup) {
            if (!file_exists("{$path}.backup")) {
                $this->fs->cp($path, "{$path}.backup");
            }
            if (file_exists("{$path}.backup")) {
                $file = file_get_contents("{$path}.backup");
                $file = $this->replaceByString($file);
                file_put_contents($path, $file);

                return true;
            }
        } else if (file_exists($path)) {
            $file = file_get_contents($path);
            $file = $this->replaceByString($file);
            file_put_contents($path, $file);

            return true;
        }

        return false;
    }

    public function replaceByString($text)
    {
        $this->init();
        return str_replace($this->finds, $this->replaces, $text);
    }

    public function replaceToTemplateByString($text)
    {
        $this->init();

        $userName = $this->system->getUserName();

        return str_replace(
            [
                $this->config->getRootDir(),
                "'{$userName}'",
                "\"{$userName}\"",
                "/{$userName}/",
                "\\{$userName}\\",
            ],
            [
                '{ROOT_DIR}',
                "'{USER}'",
                '"{USER}"',
                '/{USER}/',
                "\\{USER}\\",
            ],
            $text
        );
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        $this->init();
        return $this->height;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        $this->init();
        return $this->width;
    }

    /**
     * @return string
     */
    public function getUserName()
    {
        $this->init();
        return $this->userName;
    }
}
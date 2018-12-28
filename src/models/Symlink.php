<?php

class Symlink
{
    private $command;
    private $config;
    private $fs;
    private $extensions;

    /**
     * Symlink constructor.
     * @param Config $config
     * @param Command $command
     * @param FileSystem $fs
     */
    public function __construct(Config $config, Command $command, FileSystem $fs)
    {
        $this->config     = $config;
        $this->command    = $command;
        $this->fs         = $fs;
        $this->extensions = ['cfg', 'conf', 'ini', 'inf', 'log', 'sav', 'save', 'config', 'con', 'profile', 'ltx'];
    }

    /**
     * @return array
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    public function replace($path)
    {
        $path = trim($path, " \t\n\r\0\x0B/\\");

        if (!$path || !file_exists($this->config->getDataDir() . "/{$path}")) {
            return false;
        }

        $symlinks  = $this->config->getSymlinksDir();
        $_symlinks = $this->config->getDataSymlinksDir();
        $data      = $this->config->getDataDir();

        $this->fs->mkdirs([$symlinks, $_symlinks]);
        $this->fs->mv("{$data}/{$path}","{$_symlinks}/{$path}");
        $this->cloneDir($this->fs->relativePath("{$_symlinks}/{$path}", $data));
        $this->fs->link("{$symlinks}/{$path}", "{$data}/{$path}");

        return true;
    }

    public function cloneDir($path)
    {
        $path = trim($path, " \t\n\r\0\x0B/\\");

        if (!$path || !file_exists($this->config->getDataDir() . "/{$path}")) {
            return false;
        }

        $symlinks  = $this->config->getSymlinksDir();
        $_symlinks = $this->config->getDataSymlinksDir();
        $data      = $this->config->getDataDir();

        $in  = "{$data}/{$path}";
        $out = "{$symlinks}/" . $this->fs->relativePath($in, $_symlinks);
        $this->fs->mkdirs([$out]);

        foreach (glob("{$in}/*") as $_path) {
            if (is_dir($_path)) {
                $this->cloneDir($this->fs->relativePath($_path, $data));
            } else {
                $basename = pathinfo($_path);
                $_out     = "{$symlinks}/" . $this->fs->relativePath($_path, $_symlinks);

                if (in_array(strtolower($basename['extension']), $this->getExtensions(), true)) {
                    $this->fs->cp($_path, $_out);
                } else {
                    $this->fs->link($_path, $_out);
                }
            }
        }

        return true;
    }

    public function getDirs()
    {
        $result = [];

        foreach (glob($this->config->getDataDir() . '/*') as $path) {

            $name = basename($path);

            if ('_symlinks' !== $name && is_dir($path) && !is_link($path)) {
                $result[] = $name;
            }
        }

        return $result;
    }
}
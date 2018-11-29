<?php

class FileSystem {

    private $command;
    private $config;

    /**
     * FileSystem constructor.
     * @param Config $config
     * @param Command $command
     */
    public function __construct(Config $config, Command $command)
    {
        $this->command = $command;
        $this->config  = $config;
    }

    public static function getDirectorySize($path)
    {
        $bytes = 0;
        $path  = realpath($path);

        if ($path !== false && $path != '' && file_exists($path)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object) {
                $bytes += $object->getSize();
            }
        }

        return $bytes;
    }

    public function relativePath($absPath, $path = null)
    {
        return trim(str_replace($path === null ? $this->config->getRootDir() : $path, '', $absPath), " \t\n\r\0\x0B/");
    }
}
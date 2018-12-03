<?php

class Mount
{
    private $command;
    private $config;
    private $folder;
    private $mounted = false;

    /**
     * Mount constructor.
     * @param Config $config
     * @param Command $command
     * @param string $folder
     */
    public function __construct(Config $config, Command $command, $folder)
    {
        $this->config  = $config;
        $this->command = $command;
        $this->folder  = $folder;

        $this->command->umount($folder);

        if (file_exists($folder) && (file_exists("{$folder}.squashfs") || file_exists("{$folder}.zip"))) {
            @rmdir($folder);
        }

        if (file_exists("{$folder}.squashfs") && !file_exists($folder)) {
            if (!mkdir($folder, 0775) && !is_dir($folder)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $folder));
            }
            $this->mounted = true;
            $this->command->squashfuse($folder);
        }

        if ($this->mounted === false && file_exists("{$folder}.zip") && !file_exists($folder)) {
            if (!mkdir($folder, 0775) && !is_dir($folder)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $folder));
            }
            $this->mounted = true;
            $this->command->zipfuse($folder);
        }

        if ($this->mounted) {
            register_shutdown_function(function () {
                foreach (range(0, 10) as $i) {
                    if (!file_exists($this->folder)) {
                        break;
                    }

                    sleep(1);

                    $this->command->umount($this->folder);

                    if (file_exists($this->folder)) {
                        @rmdir($this->folder);
                    }
                }
            });
        }
    }

    public function isMounted()
    {
        return $this->mounted;
    }
}
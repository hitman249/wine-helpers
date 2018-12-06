<?php

class Mount
{
    private $command;
    private $config;
    private $console;
    private $folder;
    private $extension;
    private $mounted = false;

    /**
     * Mount constructor.
     * @param Config $config
     * @param Command $command
     * @param Console $console
     * @param string $folder
     */
    public function __construct(Config $config, Command $command, Console $console, $folder)
    {
        $this->config  = $config;
        $this->command = $command;
        $this->folder  = $folder;
        $this->console = $console;

        $this->mount();

        if ($this->getFolder() === $config->getWineDir()) {
            $config->updateWine();
        }

        if ($this->isMounted()) {
            register_shutdown_function(function () { if ($this->isMounted()) $this->umount(); });
        }
    }

    /**
     * @return bool
     */
    public function isMounted()
    {
        return $this->mounted;
    }

    /**
     * @return string
     */
    public function getFolder()
    {
        return $this->folder;
    }

    /**
     * @return string|null
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * @return bool
     */
    public function mount()
    {
        if (!$this->console->lock()) {
            return false;
        }

        $folder = $this->folder;

        $this->umount();

        if ($this->mounted === false && file_exists("{$folder}.squashfs") && !file_exists($folder)) {
            if (!mkdir($folder, 0775) && !is_dir($folder)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $folder));
            }
            $this->extension = 'squashfs';
            $this->mounted = true;
            $this->command->squashfuse($folder);
        }

        if ($this->mounted === false && file_exists("{$folder}.zip") && !file_exists($folder)) {
            if (!mkdir($folder, 0775) && !is_dir($folder)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $folder));
            }
            $this->extension = 'zip';
            $this->mounted = true;
            $this->command->zipfuse($folder);
        }

        return $this->mounted;
    }

    /**
     * @return bool
     */
    public function umount()
    {
        if (!$this->console->lock()) {
            return false;
        }

        foreach (range(0, 5) as $i) {
            if (!file_exists($this->folder)) {
                $this->mounted = false;
                $this->extension = null;
                break;
            }

            $this->command->umount($this->folder);

            if (file_exists($this->folder) && (file_exists("{$this->folder}.squashfs") || file_exists("{$this->folder}.zip"))) {
                @rmdir($this->folder);
            }

            if (file_exists($this->folder) && (file_exists("{$this->folder}.squashfs") || file_exists("{$this->folder}.zip"))) {
                sleep(1);
            } else {
                $this->extension = null;
                $this->mounted = false;
            }
        }

        return $this->mounted;
    }
}
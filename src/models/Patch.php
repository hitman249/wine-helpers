<?php

class Patch
{
    private $config;
    private $command;
    private $fs;
    private $wine;
    private $index = 0;
    private $snapshot;
    private $runnable = false;

    /**
     * Patch constructor.
     * @param Config $config
     * @param Command $command
     * @param FileSystem $fs
     * @param Wine $wine
     */
    public function __construct(Config $config, Command $command, FileSystem $fs, Wine $wine, Snapshot $snapshot)
    {
        $this->command  = $command;
        $this->config   = $config;
        $this->fs       = $fs;
        $this->wine     = $wine;
        $this->snapshot = $snapshot;

        $this->fs->rm($this->config->getPatchAutoDir());
    }

    public function isEnabled()
    {
        return false === $this->runnable && $this->config->isGenerationPatchesMode();
    }

    /**
     * @param callable $callback
     * @return string|null
     */
    public function create($callback)
    {
        gc_collect_cycles();

        if (!$callback) {
            return null;
        }

        if ($this->isEnabled()) {
            $this->runnable = true;
            if (!file_exists($this->config->getPatchAutoDir())) {
                $this->fs->mkdirs([$this->config->getPatchAutoDir()]);
            }
            if (!file_exists($this->config->getPatchApplyDir())) {
                $this->fs->mkdirs([$this->config->getPatchApplyDir()]);
            }

            $this->snapshot->createBefore();
            $result = $callback();
            $this->snapshot->createAfter();
            $this->movePatch($this->snapshot->getPatchDir());

            $this->runnable = false;
            gc_collect_cycles();

            return $result;
        }

        return $callback();
    }

    private function movePatch($path)
    {
        if (!file_exists($path)) {
            return false;
        }

        $auto   = $this->config->getPatchAutoDir();
        $result = $this->fs->mv($path, "{$auto}/{$this->index}");
        $this->index++;

        return $result;
    }
}
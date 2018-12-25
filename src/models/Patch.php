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

    public function apply()
    {
        if ($this->config->isGenerationPatchesMode() || !file_exists($this->config->getPrefixFolder()) || !file_exists($this->config->getPatchApplyDir())) {
            return false;
        }

        $dirs = glob($this->config->getPatchApplyDir() . '/*');
        natsort($dirs);

        foreach ($dirs as $path) {
            if (file_exists("{$path}/files.tar.gz")) {
                $this->unpack("{$path}/files.tar.gz");
            } elseif (is_dir($path) && file_exists("{$path}/files")) {
                $files = glob("{$path}/files/*");
                $trim = "{$path}/files/";
                foreach ($files as $patchItem) {
                    $name = basename($patchItem);
                    $fileRelativePath = $this->fs->relativePath($patchItem, $trim);

                    if (!is_dir($patchItem)) {
                        $out = $this->config->getPrefixDriveC() . "/{$fileRelativePath}";
                        if (file_exists($out)) {
                            unlink($out);
                        }
                        $this->fs->cp($patchItem, $out, true);

                    } elseif ('users' === $name) {
                        $users = glob("{$patchItem}/*");
                        foreach ($users as $user) {
                            $name = basename($user);
                            $userRelativePath = $this->fs->relativePath($user, $trim);
                            if (!is_dir($user)) {
                                $out = $this->config->getPrefixDriveC() . "/{$userRelativePath}";
                                if (file_exists($out)) {
                                    unlink($out);
                                }
                                $this->fs->cp($user, $out, true);
                            } elseif ('default' === $name) {
                                $userName = app('start')->getSystem()->getUserName();
                                $out = $this->config->getPrefixDriveC() . "/users/{$userName}";
                                $this->fs->cp($user, $out, true);
                            } else {
                                $out = $this->config->getPrefixDriveC() . "/{$userRelativePath}";
                                $this->fs->cp($user, $out, true);
                            }
                        }
                    } else {
                        $out = $this->config->getPrefixDriveC() . "/{$fileRelativePath}";
                        $this->fs->cp($patchItem, $out, true);
                    }
                }
            }
        }

        return $dirs ? true : false;
    }

    public function getRegistryFiles()
    {
        if ($this->config->isGenerationPatchesMode() || !file_exists($this->config->getPatchApplyDir())) {
            return [];
        }

        $result = [];

        $dirs = glob($this->config->getPatchApplyDir() . '/*');
        natsort($dirs);

        foreach ($dirs as $path) {
            if (is_dir($path)) {
                $regs = glob("{$path}/*.reg");
                natsort($regs);

                foreach ($regs as $reg) {
                    $result[] = $reg;
                }
            }
        }

        return $result;
    }

    public function unpack($path)
    {
        if (!file_exists($path)) {
            return false;
        }

        $parent = dirname($path);

        $driveC = $this->config->getPrefixDriveC();

        $this->command->run("cd \"{$parent}\" && tar -xzf \"{$path}\" -C \"{$driveC}\"");

        return true;
    }
}
<?php

class Build
{
    private $command;
    private $config;
    private $system;
    private $fs;

    /**
     * Build constructor.
     * @param Config $config
     * @param Command $command
     * @param System $system
     * @param FileSystem $fs
     */
    public function __construct(Config $config, Command $command, System $system, FileSystem $fs)
    {
        $this->config     = $config;
        $this->command    = $command;
        $this->system     = $system;
        $this->fs         = $fs;
    }

    public function checkSupportReset()
    {
        $root = $this->config->getRootDir();

        return !(!file_exists($this->config->getDataFile())
            || !file_exists("{$root}/static.tar.gz")
            || !file_exists("{$root}/extract.sh"));
    }

    public function reset()
    {
        if (!$this->checkSupportReset()) {
            return false;
        }

        foreach (app('start')->getMountes() as $mount) {
            /** @var Mount $mount */
            $mount->umount();
        }

        $root     = $this->config->getRootDir();
        $gameInfo = $this->config->getGameInfoDir();

        foreach (glob("{$gameInfo}/*") as $item) {
            $name = basename($item);
            if ($name !== 'data.squashfs') {
                $this->command->run("rm -rf \"{$item}\"");
            }
        }

        foreach (glob("{$root}/*") as $item) {
            $name = basename($item);
            if (!in_array($name, ['game_info', 'extract.sh', 'static.tar.gz'], true)) {
                $this->command->run("rm -rf \"{$item}\"");
            }
        }

        if (file_exists("{$root}/libs")) {
            $this->command->run("rm -rf \"{$root}/libs\"");
        }

        return true;
    }

    public function build($isPrefix = false)
    {
        $root     = $this->config->getRootDir();
        $gameDir  = basename($root);
        $userName = $this->system->getUserName();
        $gameInfo = $this->config->getGameInfoDir();
        $gameData = $this->config->getDataDir();

        if (file_exists("{$root}/build")) {
            $this->command->run("rm -rf \"{$root}/build\"");
        }

        if (!mkdir("{$root}/build/{$gameDir}/static/game_info", 0775, true) && !is_dir("{$root}/build/{$gameDir}/static/game_info")) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', "{$root}/build/{$gameDir}/static/game_info"));
        }
        if (!mkdir("{$root}/build/{$gameDir}/game_info", 0775, true) && !is_dir("{$root}/build/{$gameDir}/game_info")) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', "{$root}/build/{$gameDir}/game_info"));
        }

        foreach (glob("{$root}/*.png") as $path) {
            $this->command->run("cp -a --link \"{$path}\" \"{$root}/build/{$gameDir}/static/\"");
        }

        if (file_exists("{$root}/wine.squashfs")) {
            $this->command->run("cp -a --link \"{$root}/wine.squashfs\" \"{$root}/build/{$gameDir}/static/\"");
        } elseif (file_exists("{$root}/wine")) {
            $this->command->run("cp -ra --link \"{$root}/wine\" \"{$root}/build/{$gameDir}/static/wine\"");
        }

        if (file_exists("{$root}/libs")) {
            $this->command->run("cp -ra --link \"{$root}/libs\" \"{$root}/build/{$gameDir}/static/libs\"");
        }
        if (file_exists("{$root}/README.md")) {
            $this->command->run("cp -a --link \"{$root}/README.md\" \"{$root}/build/{$gameDir}/static/\"");
        }
        if (file_exists("{$root}/php")) {
            $this->command->run("cp -a --link \"{$root}/php\" \"{$root}/build/{$gameDir}/static/\"");
        }
        if (file_exists("{$root}/squashfuse")) {
            $this->command->run("cp -a --link \"{$root}/squashfuse\" \"{$root}/build/{$gameDir}/static/\"");
        }
        if (file_exists("{$root}/fuse-zip")) {
            $this->command->run("cp -a --link \"{$root}/fuse-zip\" \"{$root}/build/{$gameDir}/static/\"");
        }
        if (file_exists("{$root}/start")) {
            $this->command->run("cp -a --link \"{$root}/start\" \"{$root}/build/{$gameDir}/static/\"");
        }
        if ($isPrefix && file_exists("{$root}/prefix")) {
            $this->command->run("cp -ra --link \"{$root}/prefix\" \"{$root}/build/{$gameDir}/static/prefix\"");
            if ($userName) {
                $userFolder = "{$root}/build/{$gameDir}/static/prefix/drive_c/users/{$userName}";
                if (file_exists($userFolder)) {
                    $this->command->run("rm -rf \"{$userFolder}\"");
                }
            }
        }

        $skip = [
            'data',
            'dataold',
            'data_old',
            'data.old',
            'databak',
            'data_bak',
            'data.bak',
            'test',
            'testold',
            'test_old',
            'test.old',
            'testbak',
            'test_bak',
            'test.bak',
            'test1',
            'test2',
            'test3',
            'test4',
            'data1',
            'data2',
            'data3',
            'data4',
            'data.squashfs',
            'data1.squashfs',
            'data2.squashfs',
            'data.zip',
            'data1.zip',
            'data2.zip',
            'logs',
            'cache',
        ];

        foreach (glob("{$gameInfo}/*") as $path) {
            $file = basename($path);

            if (in_array($file, $skip, true)) {
                continue;
            }

            if (is_dir($path)) {
                $this->command->run("cp -ra --link \"{$path}\" \"{$root}/build/{$gameDir}/static/game_info/{$file}\"");
            } else {
                $this->command->run("cp -a --link \"{$path}\" \"{$root}/build/{$gameDir}/static/game_info/\"");
            }
        }

        if (file_exists("{$gameInfo}/data.squashfs")) {
            $this->command->run("cp -a --link \"{$gameInfo}/data.squashfs\" \"{$root}/build/{$gameDir}/game_info/\"");
        } elseif (file_exists("{$gameInfo}/data.zip")) {
            $this->command->run("cp -a --link \"{$gameInfo}/data.zip\" \"{$root}/build/{$gameDir}/game_info/\"");
        } elseif (file_exists($gameData)) {
            $this->command->run("cp -rav --link \"{$gameData}\" \"{$root}/build/{$gameDir}/game_info/data\"");
        }

        $this->command->run("tar -cvzf \"{$root}/build/{$gameDir}/static.tar.gz\" -C \"{$root}/build/{$gameDir}/static\" .");
        $this->command->run("rm -rf \"{$root}/build/{$gameDir}/static/\"");

        /**
         * build/extract.sh
         */
        file_put_contents("{$root}/build/{$gameDir}/extract.sh",
            "#!/bin/sh

cd -P -- \"$(dirname -- \"$0\")\"

tar -xvf ./static.tar.gz

chmod +x ./start"
        );

        $this->command->run("chmod +x \"{$root}/build/{$gameDir}/extract.sh\"");
    }
}
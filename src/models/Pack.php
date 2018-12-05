<?php

class Pack
{
    private $command;
    private $config;
    private $fs;

    /**
     * Pack constructor.
     * @param Config $config
     * @param Command $command
     * @param FileSystem $fs
     */
    public function __construct(Config $config, Command $command, FileSystem $fs)
    {
        $this->config  = $config;
        $this->command = $command;
        $this->fs      = $fs;
    }

    public function pack($folder)
    {
        $mount = $this->getMount($folder);

        if (false === $mount || $mount->isMounted() || !file_exists($folder)) {
            return false;
        }

        if ($this->isMksquashfs()) {

            if (file_exists("{$folder}.squashfs")) {
                @unlink("{$folder}.squashfs");
            }

            if ($folder === $this->config->getWineDir() && file_exists("{$folder}/bin")) {
                $this->command->run('chmod +x -R ' . Text::quoteArgs("{$folder}/bin"));
            }

            $folderName = basename($folder);

            $cmd = "mksquashfs \"{$folder}\" \"{$folder}.squashfs\" -b 1048576 -comp gzip -Xcompression-level 9";

            if ('wine' === $folderName) {
                $cmd = "mksquashfs \"{$folder}\" \"{$folder}.squashfs\" -b 1048576 -comp xz -Xdict-size 100%";
            }

            $this->command->run($cmd);

            return true;
        }

        return false;
    }

    public function unpack($folder)
    {
        $mount = $this->getMount($folder);

        if (false === $mount || !$mount->isMounted() || !file_exists($folder)) {
            return false;
        }

        if (file_exists("{$folder}_tmp")) {
            $this->fs->rm("{$folder}_tmp");
        }

        $this->fs->cp($folder, "{$folder}_tmp");
        $mount->umount();

        if ($mount->isMounted() || file_exists($folder)) {
            register_shutdown_function(function () use ($folder) {
                if (!file_exists($folder) && file_exists(file_exists("{$folder}_tmp"))) {
                    $this->fs->mv("{$folder}_tmp", $folder);
                }
            });
        } else {
            $this->fs->mv("{$folder}_tmp", $folder);
        }

        return true;
    }

    public function isMksquashfs()
    {
        static $result;

        if (null === $result) {
            $result = (bool)trim($this->command->run('which mksquashfs'));
        }

        return $result;
    }

    public function getMount($folder)
    {
        $mountes = app('start')->getMountes();
        $findMount = null;

        foreach ($mountes as $mount) {
            /** @var Mount $mount */
            if ($mount->getFolder() === $folder) {
                $findMount = $mount;
                break;
            }
        }

        if (!$findMount) {
            return false;
        }

        return $findMount;
    }

    public function getMountes()
    {
        $result  = [];
        $mountes = app('start')->getMountes();

        foreach ($mountes as $mount) {
            /** @var Mount $mount */
            if ($mount->isMounted()) {
                $result[] = $mount->getFolder() . '.' .$mount->getExtension();
            }
        }

        return $result;
    }
}
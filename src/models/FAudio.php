<?php

class FAudio
{
    private $config;
    private $command;
    private $fs;
    private $wine;
    private $disk;
    private $data;

    /**
     * FAudio constructor.
     * @param Config $config
     * @param Command $command
     * @param FileSystem $fs
     * @param Wine $wine
     */
    public function __construct(Config $config, Command $command, FileSystem $fs, Wine $wine)
    {
        $this->command = $command;
        $this->config  = $config;
        $this->fs      = $fs;
        $this->wine    = $wine;
    }

    private function init()
    {
        if (null === $this->disk) {
            $this->disk = new YandexDisk('https://yadi.sk/d/IrofgqFSqHsPu/faudio_builds');
            $this->data = $this->disk->getList();
            natsort($this->data);
        }
    }

    private function getFileLatest()
    {
        $this->init();

        $value = end($this->data);
        $key   = key($this->data);

        return ['id' => $key, 'path' => $value];
    }

    public function version()
    {
        $version = $this->config->wine('DRIVE_C') . '/faudio';

        if (file_exists($version)) {
            return trim(file_get_contents($version));
        }

        return '';
    }

    public function versionRemote()
    {
        $file = $this->getFileLatest();
        $fileName = array_filter(explode('.', basename($file['path'])),
            function ($t) { return !in_array($t, ['xz', 'bz', 'bz2', 'zip', 'tar', 'gz'], true); });
        $fileName = implode('.', $fileName);

        return $fileName;
    }

    /**
     * @param callable|null $logCallback
     * @return bool
     */
    public function update($logCallback = null)
    {
        if (!$this->config->getBool('script', 'faudio') || !file_exists($this->config->getPrefixFolder())) {
            return false;
        }

        $this->init();

        if (!$this->data) {
            return false;
        }

        if ($this->version() && !$this->config->getBool('script', 'faudio_autoupdate')) {
            return false;
        }

        if ($this->version() !== $this->versionRemote()) {
            $file = $this->getFileLatest();
            if ($filePath = $this->disk->download($file['id'], $this->config->getCacheDir())) {
                $dir = $this->config->getCacheDir() . '/faudio';

                if ($logCallback) {
                    $logCallback('Update FAudio.');
                }

                $this->fs->unpack($filePath, $dir);

                $dlls = [];

                if (file_exists($this->config->getWineSystem32Folder())) {
                    foreach (glob("{$dir}/x32/*.dll") as $path) {
                        $fileName = basename($path);
                        $out = $this->config->getWineSystem32Folder() . "/{$fileName}";
                        if (file_exists($out)) {
                            $this->fs->rm($out);
                        }

                        $this->fs->cp($path, $out);

                        if (!in_array($fileName, $dlls, true)) {
                            $dlls[] = $fileName;
                        }
                    }
                }
                if (file_exists($this->config->getWineSyswow64Folder())) {
                    foreach (glob("{$dir}/x64/*.dll") as $path) {
                        $fileName = basename($path);
                        $out = $this->config->getWineSyswow64Folder() . "/{$fileName}";
                        if (file_exists($out)) {
                            $this->fs->rm($out);
                        }

                        $this->fs->cp($path, $out);

                        if (!in_array($fileName, $dlls, true)) {
                            $dlls[] = $fileName;
                        }
                    }
                }

                if ($dlls) {
                    foreach ($dlls as $dll) {
                        $this->register($dll);

                        if ($logCallback) {
                            $logCallback("Register {$dll}");
                        }
                    }
                }

                if (file_exists($dir)) {
                    $this->fs->rm($dir);
                }

                file_put_contents($this->config->wine('DRIVE_C') . '/faudio', $this->versionRemote());

                return true;
            }
        }

        return false;
    }


    public function register($fileName)
    {
        $this->wine->regsvr32([$fileName]);
        $this->wine->run(['reg', 'add', 'HKEY_CURRENT_USER\\Software\\Wine\\DllOverrides', '/v', $fileName, '/d', 'native', '/f']);
    }
}
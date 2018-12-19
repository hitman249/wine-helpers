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
            $this->data = array_filter($this->disk->getList(), function ($path) { return !Text::endsWith($path, '/'); });
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
        $fileName = explode('.', basename($file['path']));

        return reset($fileName);
    }

    /**
     * @param callable|null $logCallback
     * @return bool
     */
    public function update($logCallback = null)
    {
        $this->init();

        if (!$this->config->getBool('script', 'faudio') || !file_exists($this->config->getPrefixFolder())) {
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

                if (file_exists($this->config->getWineSystem32Folder())) {
                    foreach (glob("{$dir}/x32/*.dll") as $path) {
                        $fileName = basename($path);
                        $out = $this->config->getWineSystem32Folder() . "/{$fileName}";
                        if (file_exists($out)) {
                            $this->fs->rm($out);
                        }
                        $this->fs->cp($path, $out);
                        $this->register($fileName);
                        if ($logCallback) {
                            $logCallback("Register x86 {$fileName}");
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
                        $this->register($fileName);
                        if ($logCallback) {
                            $logCallback("Register x86_64 {$fileName}");
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
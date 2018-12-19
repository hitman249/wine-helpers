<?php

class Dumbxinputemu
{
    private $config;
    private $command;
    private $fs;
    private $wine;
    private $userAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/71.0.3578.80 Chrome/71.0.3578.80 Safari/537.36';
    private $url;
    private $data;

    /**
     * Dumbxinputemu constructor.
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

        $this->url  = 'https://api.github.com/repos/kozec/dumbxinputemu/releases/latest';
        $this->data = [];

        try {
            $request  = new \Rakit\Curl\Curl($this->url);
            $request->header('User-Agent', $this->userAgent);
            $response = $request->get();
        } catch (ErrorException $e) {
            try {
                sleep(1);
                $response = $request->get();
            } catch (ErrorException $e) {
                try {
                    sleep(3);
                    $response = $request->get();
                } catch (ErrorException $e) {
                    return;
                }
            }
        }

        if ($request && !$response->error()) {
            $result = json_decode($response->getBody(), true);

            if ($result) {
                $this->data = $result;
            }
        }
    }

    public function version()
    {
        $version = $this->config->wine('DRIVE_C') . '/dumbxinputemu';

        if (file_exists($version)) {
            return trim(file_get_contents($version));
        }

        return '';
    }

    public function versionRemote()
    {
        return $this->data ? $this->data['tag_name'] : '';
    }

    private function getLatestUrlFile()
    {
        if (!$this->data || !$this->data['assets']) {
            return null;
        }

        $asset = reset($this->data['assets']);

        if ($asset) {
            return $asset['browser_download_url'];
        }

        return null;
    }

    public function getData()
    {
        return $this->data;
    }

    public function download($path)
    {
        $url = $this->getLatestUrlFile();

        if (!$url) {
            return '';
        }

        try {
            ini_set('memory_limit', '-1');
            $request = new \Rakit\Curl\Curl($url);
            $request->header('User-Agent', $this->userAgent);
            $request->autoRedirect(5);
            $response = $request->get();
            $fileName = basename($url);
            $pathFile = "{$path}/{$fileName}";
            file_put_contents($pathFile, $response->getBody());

            return $pathFile;
        } catch (ErrorException $e) {}

        return '';
    }

    /**
     * @param callable|null $logCallback
     * @return bool
     */
    public function update($logCallback = null)
    {
        if (!$this->config->getBool('script', 'dumbxinputemu') || !file_exists($this->config->getPrefixFolder()) || !$this->data) {
            return false;
        }

        if ($this->version() && !$this->config->getBool('script', 'dumbxinputemu_autoupdate')) {
            return false;
        }

        if ($this->version() !== $this->versionRemote()) {
            if ($filePath = $this->download($this->config->getCacheDir())) {
                $dir = $this->config->getCacheDir() . '/dumbxinputemu';

                if ($logCallback) {
                    $logCallback('Update dumbxinputemu.');
                }

                $this->fs->unpack($filePath, $dir);

                if (file_exists($this->config->getWineSystem32Folder())) {
                    foreach (glob("{$dir}/32/*.dll") as $path) {
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
                    foreach (glob("{$dir}/64/*.dll") as $path) {
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

                file_put_contents($this->config->wine('DRIVE_C') . '/dumbxinputemu', $this->versionRemote());

                return true;
            }

            return false;
        }

        return false;
    }

    public function register($fileName)
    {
        $this->wine->regsvr32([$fileName]);
        $this->wine->run(['reg', 'add', 'HKEY_CURRENT_USER\\Software\\Wine\\DllOverrides', '/v', $fileName, '/d', 'native', '/f']);
    }
}
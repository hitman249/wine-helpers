<?php

class Fixes
{
    private $config;
    private $command;
    private $fs;
    private $wine;
    private $version;

    /**
     * Fixes constructor.
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

        $this->version = $this->config->wine('DRIVE_C') . '/fixes';
    }

    /**
     * @param callable|null $logCallback
     * @return bool
     */
    public function update($logCallback = null)
    {
        if (!file_exists($this->config->getPrefixFolder())) {
            return false;
        }

        $isUpdated = false;

        $fixes = [
            'ddraw',
            'installers',
            'd3dx9',
            'internet',
            'intro',
            'xact',
            'physx',
            'font',
            'focus',
        ];

        foreach ($fixes as $fix) {
            $versionPath = $this->config->wine('DRIVE_C') . '/.' . $fix;

            if ($this->config->getBool('fixes', $fix)) {
                if (!file_exists($versionPath)) {
                    $isUpdated = true;

                    if ($logCallback) {
                        $logCallback("Apply fix {$fix}");
                    }

                    if (method_exists($this, "{$fix}Up")) {
                        app('start')->getPatch()->create(function () use ($fix, $logCallback, $versionPath) {
                            file_put_contents($versionPath, $fix);
                            $this->{"{$fix}Up"}($logCallback);
                        });
                    }
                }
            } else {
                if (file_exists($versionPath)) {
                    $isUpdated = true;

                    unlink($versionPath);

                    if (method_exists($this, "{$fix}Down")) {
                        $this->{"{$fix}Down"}($logCallback);

                        if ($logCallback) {
                            $logCallback("Rollback fix {$fix}");
                        }
                    }
                }
            }
        }

        return $isUpdated;
    }

    /**
     * @param callable|null $logCallback
     */
    public function ddrawUp($logCallback = null)
    {
        $this->register('dciman32', 'native', $logCallback);
        $this->register('ddrawex', 'native,builtin', $logCallback);
        $this->register('devenum', 'native', $logCallback);

        if (!file_exists($this->config->getDllsDir() . '/ddraw.dll')) {
            if ($path = $this->fs->download('http://files.gsc-game.com/cs-dd-patch/cs-dd-patch.exe', $this->config->getCacheDir())) {
                $this->fs->unpack($path, $this->config->getCacheDir() . '/ddraw');
                $this->fs->cp($this->config->getCacheDir() . '/ddraw/ddraw.dll', $this->config->getWineSystem32Folder() . '/ddraw.dll');
                $this->fs->rm($this->config->getCacheDir() . '/ddraw');

                if ($logCallback) {
                    $logCallback("Add system32 ddraw.dll");
                }
            }
        }
        $this->register('ddraw', 'native,builtin', $logCallback);

        if ($logCallback) {
            $logCallback("Install devenum");
        }
        $this->wine->winetricks(['devenum']);
    }

    /**
     * @param callable|null $logCallback
     */
    public function ddrawDown($logCallback = null)
    {
        $this->unregister('dciman32', $logCallback);
        $this->unregister('ddraw', $logCallback);
        $this->unregister('ddrawex', $logCallback);
        $this->unregister('devenum', $logCallback);
        $this->fs->rm($this->config->getDllsDir() . '/ddraw.dll');
    }

    /**
     * @param callable|null $logCallback
     */
    public function installersUp($logCallback = null)
    {
        if ($logCallback) {
            $logCallback("Install gdiplus, mfc42");
        }
        $this->wine->winetricks(['gdiplus', 'mfc42']);
    }

    /**
     * @param callable|null $logCallback
     */
    public function d3dx9Up($logCallback = null)
    {
        if ($logCallback) {
            $logCallback("Install d3dx9, d3dcompiler_43");
        }
        $this->wine->winetricks(['d3dx9', 'd3dcompiler_43']);
    }

    /**
     * @param callable|null $logCallback
     */
    public function internetUp($logCallback = null)
    {
        if ($logCallback) {
            $logCallback("Install winhttp, wininet, directplay");
        }
        $this->wine->winetricks(['winhttp', 'wininet', 'directplay']);
    }

    /**
     * @param callable|null $logCallback
     */
    public function introUp($logCallback = null)
    {
        $items = [
            'qasf',
            'qdvd',
            'quartz',
            'amstream',
            'avifil32',
            'dirac',
            'l3codecx',
            'ffdshow',
            'cinepak',
            'xvid',
            'binkw32',
            'ogg',
            'windowscodecs',
            'wmp9',
            'quicktime76',
            'icodecs'
        ];

        if ($logCallback) {
            $logCallback("Install " . implode(', ', $items));
        }
        $this->wine->winetricks($items);
    }

    /**
     * @param callable|null $logCallback
     */
    public function xactUp($logCallback = null)
    {
        $this->wine->winetricks(['xact']);
    }

    /**
     * @param callable|null $logCallback
     */
    public function physxUp($logCallback = null)
    {
        $this->wine->winetricks(['physx']);
    }

    /**
     * @param callable|null $logCallback
     */
    public function fontUp($logCallback = null)
    {
        $this->wine->winetricks(['allfonts']);
    }

    /**
     * @param callable|null $logCallback
     */
    public function focusUp($logCallback = null)
    {
        $this->wine->run(['reg', 'add', 'HKEY_CURRENT_USER\\Software\\Wine\\X11 Driver', '/v', 'GrabFullscreen', '/d', 'Y', '/f']);
        $this->wine->run(['reg', 'add', 'HKEY_CURRENT_USER\\Software\\Wine\\X11 Driver', '/v', 'UseTakeFocus', '/d', 'N', '/f']);
    }


    /**
     * @param $file
     * @param string $type
     * @param callable|null $logCallback
     */
    public function register($file, $type = 'native', $logCallback = null)
    {
        $this->wine->run(['reg', 'add', 'HKEY_CURRENT_USER\\Software\\Wine\\DllOverrides', '/v', $file, '/d', $type, '/f']);

        if ($logCallback) {
            $logCallback("Register {$file}={$type}");
        }
    }

    /**
     * @param $file
     * @param callable|null $logCallback
     */
    public function unregister($file, $logCallback = null)
    {
        $this->wine->run(['reg', 'delete', 'HKEY_CURRENT_USER\\Software\\Wine\\DllOverrides', '/v', $file, '/f']);

        if ($logCallback) {
            $logCallback("Unregister {$file}");
        }
    }
}
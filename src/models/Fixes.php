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
        $versions  = $this->versions();

        $fixes = [
            'ddraw',
            'installers',
            'd3dx9',
            'internet',
            'intro',
            'xact',
            'physx',
            'font',
        ];

        foreach ($fixes as $fix) {
            if ($this->config->getBool('fixes', $fix)) {
                if (!in_array($fix, $versions, true)) {
                    $versions[] = $fix;
                    $isUpdated = true;

                    if ($logCallback) {
                        $logCallback("Apply fix {$fix}");
                    }

                    if (method_exists($this, "{$fix}Up")) {
                        app('start')->getPatch()->create(function () use ($fix, $logCallback, $versions) {
                            file_put_contents($this->version, implode("\n", $versions));
                            $this->{"{$fix}Up"}($logCallback);
                        });
                    }
                }
            } else {
                if (in_array($fix, $versions, true)) {
                    $versions  = array_diff($versions, [$fix]);
                    $isUpdated = true;

                    if (method_exists($this, "{$fix}Down")) {
                        $this->{"{$fix}Down"}($logCallback);

                        if ($logCallback) {
                            $logCallback("Rollback fix {$fix}");
                        }
                    }
                }
            }
        }

        if ($isUpdated) {
            file_put_contents($this->version, implode("\n", $versions));
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
        if ($logCallback) {
            $logCallback("Install quartz, allcodecs, wmp9");
        }
        $this->wine->winetricks(['quartz', 'allcodecs', 'wmp9']);
    }

    /**
     * @param callable|null $logCallback
     */
    public function xactUp($logCallback = null)
    {
        if ($logCallback) {
            $logCallback("Install xact");
        }
        $this->wine->winetricks(['xact']);
    }

    /**
     * @param callable|null $logCallback
     */
    public function physxUp($logCallback = null)
    {
        if ($logCallback) {
            $logCallback("Install physx");
        }
        $this->wine->winetricks(['physx']);
    }

    /**
     * @param callable|null $logCallback
     */
    public function fontUp($logCallback = null)
    {
        if ($logCallback) {
            $logCallback("Install allfonts");
        }
        $this->wine->winetricks(['allfonts']);
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

    public function versions()
    {
        if (file_exists($this->version)) {
            return array_map('trim', explode("\n", trim(file_get_contents($this->version))));
        }

        return [];
    }
}
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
//            'ddraw',
//            'installers',
//            'd3dx9',
//            'internet',
//            'intro',
//            'xact',
//            'physx',
//            'font',
            'focus',
            'nocrashdialog',
            'cfc',
            'ddr',
            'glsl',
            'orm',
        ];

        foreach ($fixes as $fix) {
            $versionPath = $this->config->wine('DRIVE_C') . '/.' . $fix;

            $valueConfigFix = $this->config->get('fixes', $fix);
            $valueLocalFix  = file_exists($versionPath) ? trim(file_get_contents($versionPath)) : null;

            $isNotEmpty = false;

            if (in_array($fix, ['glsl'], true)) {
                $isNotEmpty = !$this->config->getBool('fixes', $fix);
            } else {
                $isNotEmpty = $this->config->getBool('fixes', $fix);
            }

            if ($isNotEmpty) {
                if (null === $valueLocalFix || $valueLocalFix !== $valueConfigFix) {
                    $isUpdated = true;

                    if ($logCallback) {
                        $logCallback("Apply fix {$fix}");
                    }

                    if (method_exists($this, "{$fix}Up")) {
                        app('start')->getPatch()->create(function () use ($fix, $logCallback, $versionPath) {
                            file_put_contents($versionPath, $this->config->get('fixes', $fix));
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
     * @deprecated
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
     * @deprecated
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
     * @deprecated
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
     * @deprecated
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
     * @deprecated
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
     * @deprecated
     * @param callable|null $logCallback
     */
    public function xactUp($logCallback = null)
    {
        $this->wine->winetricks(['xact']);
    }

    /**
     * @deprecated
     * @param callable|null $logCallback
     */
    public function physxUp($logCallback = null)
    {
        $this->wine->winetricks(['physx']);
    }

    /**
     * @deprecated
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
     * @param callable|null $logCallback
     */
    public function focusDown($logCallback = null)
    {
        $this->wine->run(['reg', 'delete', 'HKEY_CURRENT_USER\\Software\\Wine\\X11 Driver', '/v', 'GrabFullscreen', '/f']);
        $this->wine->run(['reg', 'delete', 'HKEY_CURRENT_USER\\Software\\Wine\\X11 Driver', '/v', 'UseTakeFocus', '/f']);
    }

    /**
     * @param callable|null $logCallback
     */
    public function nocrashdialogUp($logCallback = null)
    {
        $this->wine->run(['reg', 'add', 'HKEY_CURRENT_USER\\Software\\Wine\\WineDbg', '/v', 'ShowCrashDialog', '/t', 'REG_DWORD', '/d', '00000000', '/f']);
    }

    /**
     * @param callable|null $logCallback
     */
    public function nocrashdialogDown($logCallback = null)
    {
        $this->wine->run(['reg', 'delete', 'HKEY_CURRENT_USER\\Software\\Wine\\WineDbg', '/v', 'ShowCrashDialog', '/f']);
    }

    /**
     * @param callable|null $logCallback
     */
    public function cfcUp($logCallback = null)
    {
        $this->wine->run(['reg', 'add', 'HKEY_CURRENT_USER\\Software\\Wine\\Direct3D', '/v', 'CheckFloatConstants', '/d', 'enabled', '/f']);
    }

    /**
     * @param callable|null $logCallback
     */
    public function cfcDown($logCallback = null)
    {
        $this->wine->run(['reg', 'delete', 'HKEY_CURRENT_USER\\Software\\Wine\\Direct3D', '/v', 'CheckFloatConstants', '/f']);
    }

    /**
     * @param callable|null $logCallback
     */
    public function ddrUp($logCallback = null)
    {
        $this->wine->run(['reg', 'add', 'HKEY_CURRENT_USER\\Software\\Wine\\Direct3D', '/v', 'DirectDrawRenderer', '/d', $this->config->get('fixes', 'ddr'), '/f']);
    }

    /**
     * @param callable|null $logCallback
     */
    public function ddrDown($logCallback = null)
    {
        $this->wine->run(['reg', 'delete', 'HKEY_CURRENT_USER\\Software\\Wine\\Direct3D', '/v', 'DirectDrawRenderer', '/f']);
    }

    /**
     * @param callable|null $logCallback
     */
    public function glslUp($logCallback = null)
    {
        $this->wine->run(['reg', 'add', 'HKEY_CURRENT_USER\\Software\\Wine\\Direct3D', '/v', 'UseGLSL', '/d', 'disabled', '/f']);
    }

    /**
     * @param callable|null $logCallback
     */
    public function glslDown($logCallback = null)
    {
        $this->wine->run(['reg', 'delete', 'HKEY_CURRENT_USER\\Software\\Wine\\Direct3D', '/v', 'UseGLSL', '/f']);
    }

    /**
     * @param callable|null $logCallback
     */
    public function ormUp($logCallback = null)
    {
        $this->wine->run(['reg', 'add', 'HKEY_CURRENT_USER\\Software\\Wine\\Direct3D', '/v', 'OffscreenRenderingMode', '/d', $this->config->get('fixes', 'orm'), '/f']);
    }

    /**
     * @param callable|null $logCallback
     */
    public function ormDown($logCallback = null)
    {
        $this->wine->run(['reg', 'delete', 'HKEY_CURRENT_USER\\Software\\Wine\\Direct3D', '/v', 'OffscreenRenderingMode', '/f']);
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
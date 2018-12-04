<?php

class Config {

    private $repo;
    private $context;
    private $gameInfoDir;
    private $rootDir;
    private $dataDir;
    private $dataFile;
    private $additionalDir;
    private $dllsDir;
    private $dlls64Dir;
    private $hooksDir;
    private $regsDir;
    private $cacheDir;
    private $logsDir;
    private $libsDir;
    private $libs64Dir;
    private $configPath;
    private $config;
    private $wine;
    private $dxvkConfFile;
    private $hooksGpuDir;
    private $symlinksDir;
    private $dataSymlinksDir;

    public function __construct($path = null)
    {
        $this->repo            = 'https://raw.githubusercontent.com/hitman249/wine-helpers/master';
        $this->context         = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]];
        $this->rootDir         = __DIR__;
        $this->gameInfoDir     = "{$this->rootDir}/game_info";
        $this->dataDir         = "{$this->rootDir}/game_info/data";
        $this->dataSymlinksDir = "{$this->rootDir}/game_info/data/_symlinks";
        $this->dataFile        = "{$this->rootDir}/game_info/data.squashfs";
        $this->additionalDir   = "{$this->rootDir}/game_info/additional";
        $this->symlinksDir     = "{$this->rootDir}/game_info/additional/symlinks";
        $this->dllsDir         = "{$this->rootDir}/game_info/dlls";
        $this->dlls64Dir       = "{$this->rootDir}/game_info/dlls64";
        $this->hooksDir        = "{$this->rootDir}/game_info/hooks";
        $this->hooksGpuDir     = "{$this->rootDir}/game_info/hooks/gpu";
        $this->regsDir         = "{$this->rootDir}/game_info/regs";
        $this->cacheDir        = "{$this->rootDir}/game_info/cache";
        $this->logsDir         = "{$this->rootDir}/game_info/logs";
        $this->dxvkConfFile    = "{$this->rootDir}/game_info/dxvk.conf";
        $this->libsDir         = "{$this->rootDir}/libs/i386";
        $this->libs64Dir       = "{$this->rootDir}/libs/x86-64";
        $this->wineDir         = "{$this->rootDir}/wine";
        $this->wineFile        = "{$this->rootDir}/wine.squashfs";

        if (null !== $path) {
            $this->load($path);
        }

        $this->wine = [
            'WINEDEBUG'        => '-all',
            'WINEARCH'         => 'win32',
            'WINEDLLOVERRIDES' => '', // 'winemenubuilder.exe=d;nvapi,nvapi64,mscoree,mshtml='
            'WINEPREFIX'       => "{$this->rootDir}/prefix",
            'DRIVE_C'          => "{$this->rootDir}/prefix/drive_c",
            'WINE'             => "{$this->rootDir}/wine/bin/wine",
            'WINE64'           => "{$this->rootDir}/wine/bin/wine64",
            'REGEDIT'          => "{$this->rootDir}/wine/bin/wine\" \"regedit",
            'REGEDIT64'        => "{$this->rootDir}/wine/bin/wine64\" \"regedit",
            'WINEBOOT'         => "{$this->rootDir}/wine/bin/wine\" \"wineboot",
            'WINEFILE'         => "{$this->rootDir}/wine/bin/wine\" \"winefile",
            'WINECFG'          => "{$this->rootDir}/wine/bin/wine\" \"winecfg",
            'WINESERVER'       => "{$this->rootDir}/wine/bin/wineserver",
        ];

        if (!file_exists($this->wine['WINE']) || version_compare((new System($this, new Command($this)))->getGlibcVersion(), '2.23', '<')) {
            $this->wine['WINE']       = 'wine';
            $this->wine['WINE64']     = 'wine64';
            $this->wine['REGEDIT']    = 'wine" "regedit';
            $this->wine['REGEDIT64']  = 'wine64" "regedit';
            $this->wine['WINEBOOT']   = 'wineboot';
            $this->wine['WINEFILE']   = 'winefile';
            $this->wine['WINECFG']    = 'winecfg';
            $this->wine['WINESERVER'] = 'wineserver';
        }
    }

    public function wine($field)
    {
        $value = $this->get('wine', $field);

        if ($value === null) {
            $value = $this->wine[$field] ?: null;
        }

        return $value;
    }

    public function get($section, $field = null)
    {
        $this->load();

        if ($field !== null && empty($this->config[$section])) {
            return null;
        }

        return null === $field ? $this->config[$section] : $this->config[$section][$field];
    }

    public function set($section, $field, $value)
    {
        $this->load();
        $this->config[$section][$field] = $value;
    }

    public function getBool($section, $field)
    {
        return (bool)$this->get($section, $field);
    }

    public function getInt($section, $field)
    {
        $result = $this->get($section, $field);

        if (is_array($result)) {
            return array_map('intval', $result);
        }

        return (int)$result;
    }

    public function getFloat($section, $field)
    {
        $result = $this->get($section, $field);

        if (is_array($result)) {
            return array_map('floatval', $result);
        }

        return (float)$result;
    }

    public function getGamePath()
    {
        return $this->get('game', 'path');
    }

    public function getPrefixGameFolder()
    {
        return $this->wine('DRIVE_C') . '/' . $this->getGamePath();
    }

    public function getGameAdditionalPath()
    {
        return $this->get('game', 'additional_path');
    }

    public function getGameExe()
    {
        return $this->get('game', 'exe');
    }

    public function getGameArgs()
    {
        return $this->get('game', 'cmd');
    }

    public function getGameTitle()
    {
        return $this->get('game', 'name');
    }

    public function getGameVersion()
    {
        return $this->get('game', 'version');
    }

    private function load($path = null)
    {
        if (null === $this->config) {
            if (!$path) {
                $path = $this->getConfigFile();
            } else {
                $this->configPath = $path;
            }

            if (file_exists($this->configPath)) {
                $this->config = (new FileINI($path))->get();
            } else {
                $this->config = parse_ini_string($this->getDefaultConfig(), true);
            }
        }
    }

    /**
     * @return string
     */
    public function getConfigFile()
    {
        if (null === $this->configPath) {
            $configs = $this->findConfigsPath();

            if ($configs) {
                $this->configPath = reset($configs);
            } else {
                $this->configPath = $this->getGameInfoDir() . '/game_info.ini';
            }
        }

        return $this->configPath;
    }

    public function findConfigsPath()
    {
        $configs = glob("{$this->gameInfoDir}/*.ini");
        natsort($configs);

        return $configs;
    }

    public function getContextOptions()
    {
        return $this->context;
    }

    public function getRepositoryUrl()
    {
        return $this->repo;
    }

    public function getGameInfoDir()
    {
        return $this->gameInfoDir;
    }

    public function getRootDir()
    {
        return $this->rootDir;
    }

    public function getDataDir()
    {
        return $this->dataDir;
    }

    public function getDataFile()
    {
        return $this->dataFile;
    }

    public function getWineDir()
    {
        return $this->wineDir;
    }

    public function getWineFile()
    {
        return $this->wineFile;
    }

    public function getAdditionalDir()
    {
        return $this->additionalDir;
    }

    public function getSymlinksDir()
    {
        return $this->symlinksDir;
    }

    public function getDllsDir()
    {
        return $this->dllsDir;
    }

    public function getDlls64Dir()
    {
        return $this->dlls64Dir;
    }

    public function getHooksDir()
    {
        return $this->hooksDir;
    }

    public function getHooksGpuDir()
    {
        return $this->hooksGpuDir;
    }

    public function getRegsDir()
    {
        return $this->regsDir;
    }

    public function getCacheDir()
    {
        return $this->cacheDir;
    }

    public function getLogsDir()
    {
        return $this->logsDir;
    }

    public function getLibsDir()
    {
        return $this->libsDir;
    }

    public function getLibs64Dir()
    {
        return $this->libs64Dir;
    }

    public function getDxvkConfFile()
    {
        return $this->dxvkConfFile;
    }

    public function getDefaultConfig()
    {
        return "[game]
path = \"Games\"
additional_path = \"The Super Game/bin\"
exe = \"Game.exe\"
cmd = \"-language=russian\"
name = \"The Super Game: Deluxe Edition\"
version = \"1.0.0\"
[script]
csmt = 1
winetricks = 0
dialogs = 1
autoupdate = 1

; Not use /home/user directory
sandbox = 1

; Download latest d3d11.dll and dxgi.dll
dxvk = 0
dxvk_autoupdate = 1

; Windows version (win7, winxp, win2k)
winver = \"win7\"

; Set sound driver to PulseAudio or ALSA
pulse = 1

; Auto fixed resolution, brightness, gamma for all monitors
fixres = 1
[wine]
WINEDEBUG = \"-all\"
WINEARCH = \"win32\"
WINEDLLOVERRIDES = \"\"
[window]
enable = 0
title = \"Wine\"
resolution = \"800x600\"
[dlls]
;
; Additional dlls folder logic
; Example: dll[name_file.dll] = \"nooverride\"
;
; Variables:
; \"builtin\"        - Встроенная
; \"native\"         - Сторонняя (default)
; \"builtin,native\" - Встроенная, Сторонняя
; \"native,builtin\" - Сторонняя, Встроенная
; \"nooverride\"     - Не заносить в реестр
; \"register\"       - Зарегистрировать библиотеку через regsvr32
;
; Настройки относятся только к папке dlls, которая создаёт симлинки в папку system32
;

; dll[d3d11.dll] = \"nooverride\"
; dll[l3codecx.ax] = \"register\"
[hooks]
;
; Хуки
; after_create_prefix - команды выполняются после создания префикса
; before_run_game - команды выполняются перед запуском игры
; after_exit_game - команды выполняются после завершения игры
;

; after_create_prefix[] = \"create.sh\"
; before_run_game[] = \"before.sh\"
; after_exit_game[] = \"after.sh\"
; after_exit_game[] = \"after2.sh\"
; gpu_amd[] = \"gpu/amd.sh\"
; gpu_nvidia[] = \"gpu/nvidia.sh\"
; gpu_intel[] = \"gpu/intel.sh\"
; settings[] = \"settings.sh\"
[export]
;
; Экспорт дополнительных переменных к команде запуска игры
; Примеры:
;

; DXVK_HUD=fps
; DXVK_HUD=1
; DXVK_HUD=fps,devinfo,memory
; DXVK_HUD=fps,devinfo,frametimes,memory
; DXVK_HUD=fps,devinfo,frametimes,submissions,drawcalls,pipelines,memory
; GALLIUM_HUD=simple,fps
; WINEESYNC=1
; PBA_DISABLE=1
; MESA_GLTHREAD=true
; __GL_THREADED_OPTIMIZATIONS=1
;
; Если в игре хрипит звук можно попробовать
; PULSE_LATENCY_MSEC=60

WINEESYNC=1
PBA_DISABLE=1
[replaces]
;
; При создании префикса ищет и заменяет в указанных файлах теги.
; Путь относительно позиции файла ./start
; Выполняется ДО регистрации *.reg файлов
;
; {WIDTH} - ширина монитора по умолчанию в пикселях (число)
; {HEIGHT} - высота монитора по умолчанию в пикселях (число)
; {USER} - имя пользователя
;

; file[] = \"game_info/data/example.conf\"";
    }

    public function getDefaultDxvkConfig()
    {
        return "# Create the VkSurface on the first call to IDXGISwapChain::Present,
# rather than when creating the swap chain. Some games that start
# rendering with a different graphics API may require this option,
# or otherwise the window may stay black.
# 
# Supported values: True, False
# 
# Enabled by default for:
# - Frostpunk

# dxgi.deferSurfaceCreation = False


# Enforce a stricter maximum frame latency. Overrides the application
# setting specified by calling IDXGIDevice::SetMaximumFrameLatency.
# Setting this to 0 will have no effect.
# 
# Supported values : 0 - 16

# dxgi.maxFrameLatency = 0


# Override PCI vendor and device IDs reported to the application. Can
# cause the app to adjust behaviour depending on the selected values.
#
# Supported values: Any four-digit hex number.

# dxgi.customDeviceId = 0000
# dxgi.customVendorId = 0000


# Override maximum amount of device memory and shared system memory
# reported to the application. This may fix texture streaming issues
# in games that do not support cards with large amounts of VRAM.
#
# Supported values: Any number in Megabytes.
#
# Enabled by default for:
# - Life is Feudal MMO: 4095

# dxgi.maxDeviceMemory = 0
# dxgi.maxSharedMemory = 0


# Override back buffer count for the Vulkan swap chain.
# Setting this to 0 or less will have no effect.
#
# Supported values: Any number greater than or equal to 2.

# dxgi.numBackBuffers = 0


# Overrides synchronization interval (Vsync) for presentation.
# Setting this to 0 disables vertical synchronization entirely.
# A positive value 'n' will enable Vsync and repeat the same
# image n times, and a negative value will have no effect.
#
# Supported values: Any non-negative number

# dxgi.syncInterval = -1


# Overrides present mode for vertical synchronization
# 
# Supported values are:
# - 0: FIFO (default)
# - 1: MAILBOX (allows higher frame rates than refresh rate)

# dxgi.syncMode = 0


# Enables or dsables d3d10 support.
# 
# Supported values: True, False

# d3d10.enable = True


# Handle D3D11_MAP_FLAG_DO_NOT_WAIT correctly when D3D11DeviceContext::Map()
# is called. Enabling this can potentially improve performance, but breaks
# games which do not expect Map() to return an error despite using the flag.
# 
# Supported values: True, False
#
# Enabled by default for:
# - Dishonored 2
# - Far Cry 5

# d3d11.allowMapFlagNoWait = False


# Fake Stream Output support. This reports a success code to applications
# calling CreateGeometryShaderWithStreamOutput, even if the device does
# not actually support transform feedback. Allows some games to run that
# would otherwise crash or show an error message.
#
# Supported values: True, False
#
# Enabled by default for:
# - F1 2015
# - Final Fantasy XV
# - Mafia 3
# - Overwatch

# d3d11.fakeStreamOutSupport = False


# Override the maximum feature level that a D3D11 device can be created
# with. Setting this to a higher value may allow some applications to run
# that would otherwise fail to create a D3D11 device.
#
# Supported values: 9_1, 9_2, 9_3, 10_0, 10_1, 11_0, 11_1

# d3d11.maxFeatureLevel = 11_1


# Overrides the maximum allowed tessellation factor. This can be used to
# improve performance in titles which overuse tessellation.
# 
# Supported values: Any number between 8 and 64

# d3d11.maxTessFactor = 0


# Overrides anisotropic filtering for all samplers. Set this to a positive
# value to enable AF for all samplers in the game, or to 0 in order to
# disable AF entirely. Negative values will have no effect.
# 
# Supported values: Any number between 0 and 16

# d3d11.samplerAnisotropy = -1


# Allow allocating more device memory from a Vulkan heap than the heap
# provides. May in some cases improve performance in low-memory conditions.
#
# Supported values: True, False

# dxvk.allowMemoryOvercommit = False


# Sets number of pipeline compiler threads.
# 
# Supported values:
# - 0 to automatically determine the number of threads to use
# - any positive number to enforce the thread count

# dxvk.numCompilerThreads = 0";
    }

    public function isScriptAutoupdate()
    {
        return $this->getBool('script', 'autoupdate');
    }

    public function isDxvkAutoupdate()
    {
        return $this->getBool('script', 'dxvk_autoupdate');
    }

    public function isDxvk()
    {
        return $this->getBool('script', 'dxvk');
    }

    public function isSandbox()
    {
        return $this->getBool('script', 'sandbox');
    }

    public function isPulse()
    {
        return $this->getBool('script', 'pulse');
    }

    public function isFixres()
    {
        return $this->getBool('script', 'fixres');
    }

    public function isCsmt()
    {
        return $this->getBool('script', 'csmt');
    }

    public function isPBA()
    {
        return $this->get('export', 'PBA_DISABLE') !== null && !$this->getBool('export', 'PBA_DISABLE');
    }

    public function isEsync()
    {
        return $this->getBool('export', 'WINEESYNC');
    }

    public function getDxvkConfigFile()
    {
        $file   = $this->dxvkConfFile;
        $driveC = $this->wine('DRIVE_C') . '/dxvk.conf';

        static $run = false;

        if ($run) {
            return $driveC;
        }

        if (file_exists($file) && !file_exists($driveC)) {
            $run = true;
            (new Command($this))->run("ln -sfr \"{$file}\" \"{$driveC}\"");
            $run = false;
        }

        return $driveC;
    }

    public function getDxvkCacheDir()
    {
        $dir    = $this->getCacheDir();
        $driveC = $this->wine('DRIVE_C') . '/cache';

        static $run = false;

        if ($run) {
            return $driveC;
        }

        if (!file_exists($dir)) {
            if (!mkdir($dir, 0775) && !is_dir($dir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
            }
        }

        if (!file_exists($driveC)) {
            $run = true;
            (new Command($this))->run("ln -sfr \"{$dir}\" \"{$driveC}\"");
            $run = false;
        }

        return $driveC;
    }

    public function getDxvkLogsDir()
    {
        $dir    = $this->getLogsDir();
        $driveC = $this->wine('DRIVE_C') . '/logs';

        static $run = false;

        if ($run) {
            return $driveC;
        }

        if (!file_exists($dir)) {
            if (!mkdir($dir, 0775) && !is_dir($dir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
            }
        }

        if (!file_exists($driveC)) {
            $run = true;
            (new Command($this))->run("ln -sfr \"{$dir}\" \"{$driveC}\"");
            $run = false;
        }

        return $driveC;
    }

    public function getDxvkVersion()
    {
        if (!$this->isDxvk() || !file_exists($this->wine('DRIVE_C') . '/dxvk')) {
            return '';
        }

        $version = file_get_contents($this->wine('DRIVE_C') . '/dxvk');

        return $version;
    }

    public function getWindowsVersion()
    {
        return $this->get('script' , 'winver');
    }

    public function getWineArch()
    {
        return $this->wine('WINEARCH');
    }

    public function getDataSymlinksDir()
    {
        return $this->dataSymlinksDir;
    }
}
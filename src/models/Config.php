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
    private $patchApplyDir;
    private $patchAutoDir;

    public function __construct($path = null)
    {
        $this->repo            = 'https://raw.githubusercontent.com/hitman249/wine-helpers/master';
        $this->context         = [
            'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
            'http' => ['header' => ['User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)']],
        ];
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
        $this->patchApplyDir   = "{$this->rootDir}/game_info/patches/apply";
        $this->patchAutoDir    = "{$this->rootDir}/game_info/patches/auto";
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
            'WINETASKMGR'      => "{$this->rootDir}/wine/bin/wine\" \"taskmgr",
            'WINEUNINSTALLER'  => "{$this->rootDir}/wine/bin/wine\" \"uninstaller",
            'WINEPROGRAM'      => "{$this->rootDir}/wine/bin/wine\" \"progman",
            'WINESERVER'       => "{$this->rootDir}/wine/bin/wineserver",
        ];
    }

    public function updateWine()
    {
        if ((new Wine($this, new Command($this)))->isUsedSystemWine()) {
            $this->wine['WINE']            = 'wine';
            $this->wine['WINE64']          = 'wine64';
            $this->wine['REGEDIT']         = 'wine" "regedit';
            $this->wine['REGEDIT64']       = 'wine64" "regedit';
            $this->wine['WINETASKMGR']     = 'wine" "taskmgr';
            $this->wine['WINEUNINSTALLER'] = 'wine" "uninstaller';
            $this->wine['WINEPROGRAM']     = 'wine" "progman';
            $this->wine['WINEBOOT']        = 'wineboot';
            $this->wine['WINEFILE']        = 'winefile';
            $this->wine['WINECFG']         = 'winecfg';
            $this->wine['WINESERVER']      = 'wineserver';
        } else {
            $this->wine['WINE']            = "{$this->rootDir}/wine/bin/wine";
            $this->wine['WINE64']          = "{$this->rootDir}/wine/bin/wine64";
            $this->wine['REGEDIT']         = "{$this->rootDir}/wine/bin/wine\" \"regedit";
            $this->wine['REGEDIT64']       = "{$this->rootDir}/wine/bin/wine64\" \"regedit";
            $this->wine['WINETASKMGR']     = "{$this->rootDir}/wine/bin/wine\" \"taskmgr";
            $this->wine['WINEUNINSTALLER'] = "{$this->rootDir}/wine/bin/wine\" \"uninstaller";
            $this->wine['WINEPROGRAM']     = "{$this->rootDir}/wine/bin/wine\" \"progman";
            $this->wine['WINEBOOT']        = "{$this->rootDir}/wine/bin/wine\" \"wineboot";
            $this->wine['WINEFILE']        = "{$this->rootDir}/wine/bin/wine\" \"winefile";
            $this->wine['WINECFG']         = "{$this->rootDir}/wine/bin/wine\" \"winecfg";
            $this->wine['WINESERVER']      = "{$this->rootDir}/wine/bin/wineserver";
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

    /**
     * @return array
     */
    public function getConfig()
    {
        $this->load();

        return $this->config;
    }

    /**
     * @return bool
     */
    public function save()
    {
        $update = new Update($this, new Command($this));

        return $update->updateConfig($this);
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
        return str_replace('\\', '/', trim($this->get('game', 'path'), '\\/'));
    }

    public function getPrefixGameFolder()
    {
        return $this->wine('DRIVE_C') . '/' . $this->getGamePath();
    }

    public function getPrefixFolder()
    {
        return $this->wine('WINEPREFIX');
    }

    public function isWineArch64()
    {
        return $this->wine('WINEARCH') === 'win64';
    }

    public function isWineArch32()
    {
        return !$this->isWineArch64();
    }

    public function getWineSystem32Folder()
    {
        if ($this->isWineArch64()) {
            return $this->wine('DRIVE_C') . '/windows/syswow64';
        }

        return $this->wine('DRIVE_C') . '/windows/system32';
    }

    public function getWineSyswow64Folder()
    {
        if ($this->isWineArch64()) {
            return $this->wine('DRIVE_C') . '/windows/system32';
        }

        return $this->wine('DRIVE_C') . '/windows/syswow64';
    }

    public function getGameAdditionalPath()
    {
        return str_replace('\\', '/', trim($this->get('game', 'additional_path'), '\\/'));
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
                @file_put_contents($path, $this->getDefaultConfig());
            }
        }
    }

    public function reload()
    {
        if (file_exists($this->configPath)) {
            $this->config = (new FileINI($this->configPath))->get();
        }
    }

    public function getPrefixDosdeviceDir()
    {
        return $this->getPrefixFolder() . '/dosdevices';
    }

    public function getPrefixDriveC()
    {
        return $this->wine('DRIVE_C');
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

    public function getContextOptions($field = null)
    {
        if ('User-Agent' === $field) {
            return str_replace('User-Agent: ', '', $this->context['http']['header']);
        }

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

    public function getRegistryDir()
    {
        return $this->regsDir;
    }

    public function getRegistryFiles()
    {
        if (!file_exists($this->getRegistryDir())) {
            return [];
        }

        $regs = glob($this->getRegistryDir() . '/*.reg');
        natsort($regs);

        return $regs;
    }

    public function getCacheDir()
    {
        if (!file_exists($this->cacheDir) && !mkdir($this->cacheDir, 0775, true) && !is_dir($this->cacheDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->cacheDir));
        }

        return $this->cacheDir;
    }

    public function getPatchApplyDir()
    {
        return $this->patchApplyDir;
    }

    public function getPatchAutoDir()
    {
        return $this->patchAutoDir;
    }

    public function getLogsDir()
    {
        if (!file_exists($this->logsDir) && !mkdir($this->logsDir, 0775, true) && !is_dir($this->logsDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->logsDir));
        }

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
        return '[game]

path = "Games"
additional_path = "The Super Game/bin"
exe = "Game.exe"
cmd = "-language=russian"
name = "The Super Game: Deluxe Edition"
version = "1.0.0"

[wine]

WINEARCH = "win32"
WINEDLLOVERRIDES = ""
WINEDEBUG = "-all"

[export]

;
; Export additional variables
; Examples:
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
; If the game wheezing sound, you can try
; PULSE_LATENCY_MSEC=60
; SDL_AUDIODRIVER=directsound

WINEESYNC=1
PBA_DISABLE=1
[script]

;
; Automatic patch generation MODE.
;
; See folder ./game_info/patches/auto
;
; To apply, move the patches to the folder ./game_info/patches/apply
;
; When enabled, patches do not apply (only creates)!
;
generation_patches_mode = 0


;
; Autoupdate this script the latest version.
; https://github.com/hitman249/wine-helpers
;
autoupdate = 1


;
; Download the latest DXVK.
; https://github.com/doitsujin/dxvk
; Dxvk versions: dxvk101, dxvk100, dxvk96, dxvk95 other. Empty from latest.
;
dxvk = 0
dxvk_version = ""
dxvk_autoupdate = 1


;
; Required for determining display manner FPS.
;
dxvk_d3d10 = 0


;
; Download the latest D9VK.
; https://github.com/Joshua-Ashton/d9vk
; D9vk versions: d9vk010, d9vk011 other. Empty from latest.
;
d9vk = 0
d9vk_version = ""
d9vk_autoupdate = 1


;
; Download the latest dumbxinputemu.
; https://github.com/kozec/dumbxinputemu
;
dumbxinputemu = 0
dumbxinputemu_autoupdate = 1


;
; Download the latest FAudio.
; https://github.com/FNA-XNA/FAudio
;
faudio = 0
faudio_autoupdate = 1


;
; winetricks_to_install = "d3dx9 xact"
;
winetricks_to_install = ""


;
; Windows version (win10, win7, winxp, win2k).
;
winver = "win7"


;
; CSMT (Commandstream multithreading) for better graphic performance.
;
csmt = 1


;
; Not use /home/user directory.
;
sandbox = 1

;
; Set sound driver to PulseAudio or ALSA.
;
pulse = 1

;
; Auto fixed resolution, brightness, gamma for all monitors.
;
fixres = 1

[fixes]

;
; Fix focus
;
focus = 0


;
; No crash dialog
; Values: 0(default), 1
;
nocrashdialog = 0


;
; CheckFloatConstants
; Values: 0(default), 1
;
cfc = 0


;
; DirectDrawRenderer
; Values: ""(default), "gdi", "opengl"
;
ddr = ""


;
; Use GLSL shaders (1) or ARB shaders (0) (faster, but sometimes breaks)
; Values: 0, 1(default)
;
glsl = 1


;
; OffscreenRenderingMode
; Values: ""(default), "fbo", "backbuffer"
;
orm = ""

[window]

enable = 0

;
; resolution = "auto"
; resolution = "800x600"
;
resolution = "auto"

[dlls]

;
; Additional dlls folder logic
; Example: dll[name_file.dll] = "nooverride"
;
; Variables:
; "builtin"        - Builtin
; "native"         - External (default)
; "builtin,native" - Builtin, External
; "native,builtin" - External, Builtin
; "nooverride"     - Do not register
; "register"       - Register via regsvr32
;

; dll[d3d11.dll] = "nooverride"
; dll[l3codecx.ax] = "register"

[hooks]

;
; Hooks
; after_create_prefix - commands are executed after prefix creation
; before_run_game     - commands are executed before the game start
; after_exit_game     - commands are executed after the game exit
;

; after_create_prefix[] = "create.sh"
; before_run_game[] = "before.sh"
; after_exit_game[] = "after.sh"
; after_exit_game[] = "after2.sh"
; gpu_amd[] = "gpu/amd.sh"
; gpu_nvidia[] = "gpu/nvidia.sh"
; gpu_intel[] = "gpu/intel.sh"

[replaces]

;
; When creating a prefix, it searches for and replaces tags in the specified files.
; Path relative to the position of the ./start file
; Performed BEFORE registering * .reg files
;
; {WIDTH}        - default monitor width in pixels (number)
; {HEIGHT}       - default monitor height in pixels (number)
; {USER}         - username
; {DOSDEVICES}   - Full path to "/.../prefix/dosdevice"
; {DRIVE_C}      - Full path to "/.../prefix/drive_c"
; {PREFIX}       - Full path to "/.../prefix"
; {ROOT_DIR}     - Full path to game folder
; {HOSTNAME}     - See command: hostname
;

; file[] = "game_info/data/example.conf"';
    }

    public function getDefaultDxvkConfig()
    {
        return "# Create the VkSurface on the first call to IDXGISwapChain::Present,
# rather than when creating the swap chain. Some games that start
# rendering with a different graphics API may require this option,
# or otherwise the window may stay black.
# 
# Supported values: True, False

# dxgi.deferSurfaceCreation = False
# d3d9.deferSurfaceCreation = False


# Enforce a stricter maximum frame latency. Overrides the application
# setting specified by calling IDXGIDevice::SetMaximumFrameLatency.
# Setting this to 0 will have no effect.
# 
# Supported values : 0 - 16

# dxgi.maxFrameLatency = 0
# d3d9.maxFrameLatency = 0


# Override PCI vendor and device IDs reported to the application. Can
# cause the app to adjust behaviour depending on the selected values.
#
# Supported values: Any four-digit hex number.

# dxgi.customDeviceId = 0000
# dxgi.customVendorId = 0000

# d3d9.customDeviceId = 0000
# d3d9.customVendorId = 0000


# Report Nvidia GPUs as AMD GPUs by default. This is enabled by default
# to work around issues with NVAPI, but may cause issues in some games.
#
# Supported values: True, False

# dxgi.nvapiHack = True



# Override maximum amount of device memory and shared system memory
# reported to the application. This may fix texture streaming issues
# in games that do not support cards with large amounts of VRAM.
#
# Supported values: Any number in Megabytes.

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

# dxgi.syncInterval   = -1
# d3d9.presentInterval = -1


# Toggles asynchronous present.
#
# Off-loads presentation to the queue submission thread in
# order to reduce stalling on the main rendering thread and
# improve performance.
#
# Supported values:
# - Auto: Enable on certain drivers
# - True / False: Always enable / disable

# dxgi.asyncPresent = Auto
# d3d9.asyncPresent = Auto


# Enables or dsables d3d10 support.
# 
# Supported values: True, False

# d3d10.enable = True


# Handle D3D11_MAP_FLAG_DO_NOT_WAIT correctly when D3D11DeviceContext::Map()
# is called. Enabling this can potentially improve performance, but breaks
# games which do not expect Map() to return an error despite using the flag.
# 
# Supported values: True, False

# d3d11.allowMapFlagNoWait = False


# Performs range check on dynamically indexed constant buffers in shaders.
# This may be needed to work around a certain type of game bug, but may
# also introduce incorrect behaviour.
#
# Supported values: True, False

# d3d11.constantBufferRangeCheck = False


# Assume single-use mode for command lists created on deferred contexts.
# This may need to be disabled for some applications to avoid rendering
# issues, which may come at a significant performance cost.
#
# Supported values: True, False

# d3d11.dcSingleUseMode = True


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


# Enables relaxed pipeline barriers around UAV writes.
# 
# This may improve performance in some games, but may also introduce
# rendering issues. Please don't report bugs with the option enabled.
#
# Supported values: True, False

# d3d11.relaxedBarriers = False


# Overrides anisotropic filtering for all samplers. Set this to a positive
# value to enable AF for all samplers in the game, or to 0 in order to
# disable AF entirely. Negative values will have no effect.
# 
# Supported values: Any number between 0 and 16

# d3d11.samplerAnisotropy = -1
# d3d9.samplerAnisotropy  = -1


# Enables SM4-compliant division-by-zero behaviour. Enabling may reduce
# performance and / or cause issues in games that expect the default
# behaviour of Windows drivers, which also is not SM4-compliant.
#
# Supported values: True, False

# d3d11.strictDivision = False


# Clears workgroup memory in compute shaders to zero. Some games don't do
# this and rely on undefined behaviour. Enabling may reduce performance.
#
# Supported values: True, False

# d3d11.zeroWorkgroupMemory = False


# Enables the dedicated transfer queue if available
#
# If enabled, resource uploads will be performed on the
# transfer queue rather than the graphics queue. This
# may improve texture streaming performance.
#
# Supported values: True, False

# dxvk.enableTransferQueue = True


# Sets number of pipeline compiler threads.
# 
# Supported values:
# - 0 to automatically determine the number of threads to use
# - any positive number to enforce the thread count

# dxvk.numCompilerThreads = 0


# Toggles raw SSBO usage.
# 
# Uses storage buffers to implement raw and structured buffer
# views. Enabled by default on hardware which has a storage
# buffer offset alignment requirement of 4 Bytes (e.g. AMD).
# Enabling this may improve performance, but is not safe on
# hardware with higher alignment requirements.
# 
# Supported values:
# - Auto: Don't change the default
# - True, False: Always enable / disable

# dxvk.useRawSsbo = Auto


# Toggles early discard.
# 
# Uses subgroup operations to determine whether it is safe to
# discard fragments before the end of a fragment shader. This
# is enabled by default on all drivers except RADV and Nvidia.
# Enabling this may improve or degrade performance depending
# on the game and hardware, or cause other issues.
# 
# Supported values:
# - Auto: Don't change the default
# - True, False: Always enable / disable

# dxvk.useEarlyDiscard = Auto


# Reported shader model
#
# The shader model to state that we support in the device
# capabilities that the applicatation queries.
# 
# Supported values:
# - 1: Shader Model 1
# - 2: Shader Model 2
# - 3: Shader Model 3

# d3d9.shaderModel = 3


# Evict Managed on Unlock
# 
# Decides whether we should evict managed resources from
# system memory when they are unlocked entirely.
#
# Supported values:
# - True, False: Always enable / disable

# d3d9.evictManagedOnUnlock = False


# DPI Awareness
# 
# Decides whether we should call SetProcessDPIAware on device
# creation. Helps avoid upscaling blur in modern Windows on
# Hi-DPI screens/devices.
#
# Supported values:
# - True, False: Always enable / disable

# d3d9.dpiAware = True


# Strict Constant Copies
# 
# Decides whether we should always copy defined constants to
# the UBO when relative addresssing is used, or only when the
# relative addressing starts a defined constant.
#
# Supported values:
# - True, False: Always enable / disable

# d3d9.strictConstantCopies = False


# Strict Pow
# 
# Decides whether we have an opSelect for handling pow(0,0) = 0
# otherwise it becomes undefined.
#
# Supported values:
# - True, False: Always enable / disable

# d3d9.strictPow = True

# Lenient Clear
#
# Decides whether or not we fastpath clear anyway if we are close enough to
# clearing a full render target.
#
# Supported values:
# - True, False: Always enable / disable

# d3d9.lenientClear = False

# Max available memory
#
# Changes the max initial value used in tracking and GetAvailableTextureMem
#
# Supported values:
# - Any uint32_t

# d3d9.maxAvailableMemory = 4294967295";
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

    public function isD9vkAutoupdate()
    {
        return $this->getBool('script', 'd9vk_autoupdate');
    }

    public function isD9vk()
    {
        return $this->getBool('script', 'd9vk');
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

    public function isGenerationPatchesMode()
    {
        return $this->getBool('script', 'generation_patches_mode');
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

    public function versionPrefix()
    {
        $version = $this->wine('WINEPREFIX') . '/version';

        if (file_exists($version)) {
            return trim(file_get_contents($version));
        }

        return '';
    }
}
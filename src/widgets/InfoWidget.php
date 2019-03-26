<?php

class InfoWidget extends AbstractWidget {

    /** @var SelectWidget */
    private $data;
    /** @var PrintWidget */
    private $windowPrint;

    private $callback;

    public function init()
    {
        if (null === $this->window) {
            $windowWidth  = $this->getParentWindow()->getWidth();
            $windowHeight = $this->getParentWindow()->getHeight();

            $menuWidth  = $this->data->getWidth();
            $menuHeight = $this->data->getHeight();

            $this->window = new \NcursesObjects\Window($windowWidth - $menuWidth - 3, $windowHeight - 2, $menuWidth + 1, 1);
        }

        if (null === $this->windowPrint) {
            $this->windowPrint = new PrintWidget($this->window);
        }
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function refresh()
    {
        parent::refresh();
        $this->windowPrint->refresh();
    }

    public function render()
    {
        $this->init();

        $window = $this->window;

        $callback = function ($item) use (&$window) {
            if ('start' === $item['id']) {

                /** @var Config $config */
                $config = $item['config'];
                $update = app('start')->getUpdate();

                if (!$config) {
                    return;
                }

                $window->erase()->border()->title($config->getGameTitle());

                $fullPath = implode('/', array_filter([$config->getGamePath(), $config->getGameAdditionalPath(), $config->getGameExe()]));

                $items = [
                    'File:    ' . basename($config->getConfigFile()),
                    "Path:    \"C:/{$fullPath}\" ". $config->getGameArgs(),
                    'Version: ' . $config->getGameVersion(),
                    'Windows: ' . $config->getWindowsVersion(),
                    'Sandbox: ' . ($config->isSandbox() ? 'on' : 'off'),
                    'Sound:   ' . ($config->isPulse() ? 'pulse' : 'alsa'),
                    'CSMT:    ' . ($config->isCsmt() ? 'on' : 'off'),
                    'DXVK:    ' . ($config->isDxvk() ? 'on ' . (($dxvkVersion = $update->versionDxvk()) ? "({$dxvkVersion})" : '') : 'off'),
                    'PBA:     ' . ($config->isPBA() ? 'on' : 'off'),
                    'Esync:   ' . ($config->isEsync() ? 'on' : 'off'),
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('wine' === $item['id']) {
                $window->erase()->border()->title($item['name']);

                $config  = app('start')->getConfig();
                $monitor = app('start')->getMonitor();
                $wine    = new Wine($config, app('start')->getCommand());

                $items = [
                    'Utilities: Config, File Manager, Regedit.',
                    '',
                ];

                $sysWine = $wine->isUsedSystemWine() ? 'Used system wine!' : null;

                $versinPrefix = $config->versionPrefix();

                if ($versinPrefix && $versinPrefix !== $wine->version()) {
                    $items = array_merge(
                        $items,
                        [
                            '!!! Warning !!!',
                            "This prefix ({$versinPrefix}) is incompatible with the current used wine!",
                            '',
                        ]
                    );
                }

                if ($sysWine) {
                    $items = array_merge($items, [$sysWine, '']);
                }

                $items = array_merge(
                    $items,
                    [
                        'Version:   ' . $wine->version(),
                        'Arch:      ' . $config->getWineArch(),
                    ]
                );

                if ($output = $monitor->getDefaultMonitor()) {
                    $items = array_merge($items, ["Monitor:   {$output['output']} ({$output['resolution']}) primary"]);
                }

                if ($miss = $wine->getMissingLibs()) {
                    $items = array_merge($items, ['', 'Missing libs:'], $miss);
                }

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('tools' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Utilities:',
                    '',
                    ' - Create\Remove Icon',
                    ' - Pack\Unpack "wine" or "data" folder',
                    ' - Replace "data" folder to symlinks',
                    ' - Build',
                    ' - Reset game files',
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('config' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Configuration',
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('tweaks' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    '- Hardware info',
                    '- System info',
                    '- Performance tweaks',
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('sys_info' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $system = app('start')->getSystem();
                $driver = app('start')->getDriver()->getVersion();

                $items = [
                    'Distr:            ' . $system->getDistrName(),
                    'Arch:             ' . $system->getArch(),
                    'Linux:            ' . $system->getLinuxVersion(),
                    'GPU Driver:       ' . implode(', ', array_filter($driver)),
                    'Glibc:            ' . $system->getGlibcVersion(),
                    'X.Org version:    ' . $system->getXorgVersion(),
                    'vm.max_map_count: ' . $system->getVmMaxMapCount(),
                    'ulimit soft:      ' . $system->getUlimitSoft(),
                    'ulimit hard:      ' . $system->getUlimitHard(),
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('hw_info' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $system = app('start')->getSystem();

                $items = [
                    'RAM:      ' . $system->getRAM() . ' Mb',
                    'Free RAM: ' . $system->getFreeRAM() . ' Mb',
                    'CPU:      ' . $system->getCPU(),
                    'GPU:      ' . $system->getGPU(),
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('cpu_mode' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $system = app('start')->getSystem();

                $items = [];
                $title = false;
                $performance = false;
                foreach ($system->getCpuFreq() as $cpu) {
                    if ($title === false) {
                        $title = true;
                        $items[] = $cpu['name'];
                        $items[] = '';
                    }

                    if ($performance === false && $cpu['mode'] === 'performance') {
                        $performance = true;
                    }

                    $items[] = "CPU {$cpu['id']}: {$cpu['freq']} ({$cpu['mode']})";
                }

                if (!$performance) {
                    $items[] = '';
                    $items[] = 'Recommended change CPU mode to "performance".';
                }

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('dependencies' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'See file "~/game_info/logs/dependencies.log"',
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('exit' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Close this application.',
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('back' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Return to main menu.',
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('icon' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Create or remove icon file.',
                    '',
                    'Found icons dir:',
                    app('start')->getIcon()->findDir(),
                ];

                $icons = app('start')->getIcon()->findExistIcons();

                if ($icons) {
                    $items = array_merge(
                        $items,
                        ['', 'Found icons:'],
                        $icons
                    );
                }

                $icons = app('start')->getIcon()->findPng();

                if ($icons) {
                    $items = array_merge(
                        $items,
                        ['', 'Found png:'],
                        $icons
                    );
                }

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('pack' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Compressed "data" and "wine" folders.',
                ];

                if ($mountes = app('start')->getPack()->getMountes()) {
                    $items = array_merge(
                        $items,
                        ['', 'Mounted:'],
                        $mountes
                    );
                }

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('symlink' === $item['id']) {

                $window->erase()->border()->title($item['name']);
                $config = app('start')->getConfig();
                $fs     = app('start')->getFileSystem();

                $items = [
                    'Replace with a symbolic link from dir RW mode',
                    '',
                    './' . $fs->relativePath($config->getDataDir()) . '/* > ./' . $fs->relativePath($config->getSymlinksDir()) . '/*',
                    '',
                    'Skip extensions:',
                    '.' . implode(', .', app('start')->getSymlink()->getExtensions()),
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('build' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Build game to "./build" folder.',
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('reset' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Full reset files the game.',
                    '',
                    'Removes all everything except files:',
                    '',
                    './game_info/data.squashfs',
                    './extract.sh',
                    './static.tar.gz',
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('winecfg' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Configuring WINE with Winecfg.',
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('filemanager' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Use file manager to install software.',
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('regedit' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Registry Editor.',
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('recreate_prefix' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Recreate Wine prefix folder.',
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('update' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $config  = app('start')->getConfig();
                $update  = app('start')->getUpdate();

                $current = $update->version();
                $remote  = $update->versionRemote();
                $auto    = $config->isScriptAutoupdate() ? 'on' : 'off';

                $items = [
                    'Update this script' . ($config->isDxvk() ? ' or DXVK.' : '.'),
                    '',
                    "Auto update: {$auto}",
                    "Current version: {$current}",
                    "Remote version: {$remote}",
                ];

                if ($config->isDxvk()) {
                    $currentDxvk = $update->versionDxvk();
                    $remoteDxvk  = $update->versionDxvkRemote();
                    $autoDxvk    = $config->isDxvkAutoupdate() ? 'on' : 'off';

                    $items = array_merge(
                        $items,
                        [
                            '',
                            "Auto update DXVK: {$autoDxvk}",
                            "Current DXVK version: {$currentDxvk}",
                            "Remote DXVK version: {$remoteDxvk}",
                        ]
                    );
                }

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('taskmgr' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Task Manager implementation.'
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('uninstaller' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Basic program uninstaller.'
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('progman' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Program Manager implementation.'
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('change' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Change Wine version.'
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('config_patches' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Automatic patch generation MODE.',
                    'See folder ./game_info/patches/auto',
                    'To apply, move the patches to the folder ./game_info/patches/apply',
                    '',
                    'When enabled, patches do not apply (only creates)!',
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('config_esync' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Esync removes wineserver overhead for synchronization objects.',
                    '',
                    'This can increase performance for some games, especially ones',
                    'that rely heavily on the CPU.',
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('config_pba' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Patches to add a persistent buffer allocator for',
                    'faster dynamic geometry in Direct3D games.',
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('config_dxvk' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Vulkan-based D3D11 and D3D10 implementation for Linux / Wine.',
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('config_csmt' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'CSMT (Commandstream multithreading) for better graphic performance.',
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('config_pulse' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Set sound driver to PulseAudio or ALSA.',
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('config_sandbox' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Not use "/home/user" directory.',
                    'Not mount "/" entry point.',
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('config_fixres' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Auto fixed resolution, brightness, gamma for all monitors.',
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('config_dxvk_d3d10' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Required for determining display manner FPS.',
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('config_faudio' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'FAudio - Accuracy-focused XAudio reimplementation.',
                    'From <= Wine 4.2',
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('config_dumbxinputemu' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'XInput reimplementation compatibile with DirectInput controllers.'
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('config_fix_focus' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Fix focus.'
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('config_fix_nocrashdialog' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Show Wine crash dialog.'
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('config_fix_ddr' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Select what backend to use for ddraw. Valid options are:',
                    '',
                    '    gdi - Use GDI to draw on the screen',
                    '    opengl - Use OpenGL (default)',
                    '',
                    'The "gdi" option mostly exists for legacy reasons. Aside from bugs in the GL',
                    'renderer, only change this if you have a setup without OpenGL. Installing a software',
                    'GL implementation is the preferred solution in that case though.',
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('config_fix_cfc' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Range check float constants in d3d9 shaders. Use this to workaround',
                    'application bugs like https://bugs.winehq.org/show_bug.cgi?id=34052,',
                    'usually resulting in geometry glitches. Enabling this has a small performance',
                    'impact, default is disabled.',
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('config_fix_glsl' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Disable GLSL shaders, use ARB shaders (faster, but sometimes breaks)',
                    'Enable GLSL shaders (default)',
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('config_fix_orm' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Select the offscreen rendering implementation:',
                    '',
                    '    backbuffer - Render offscreen render targets in the backbuffer',
                    '    fbo - Use framebuffer objects for offscreen rendering (default)',
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('config_dxvk_version' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Set DXVK version.'
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif (strpos($item['id'], 'config_') !== false) {

                $window->erase()->border()->title($item['name']);

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update([]);

            } elseif ('galliumnine' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [];

                if (app('gui')->getWineScene()->isGalliumNineInstalled()) {
                    $items[] = 'Configuring Gallium Nine with "ninewinecfg".';
                } else {
                    $items[] = 'Install Gallium Nine.';
                    $items[] = 'Native Direct3D9 in GNU/Linux.';
                    $items[] = 'Support only AMD and Intel GPU.';
                }

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            }
        };

        $this->getData()->onChangeEvent($callback);

        $this->callback = $callback;

        $callback($this->getData()->getItem());
    }

    public function pressKey($key) {}
}
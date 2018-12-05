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

                if (!$config) {
                    return;
                }

                $window->erase()->border()->title($config->getGameTitle());

                $items = [
                    'File:    ' . basename($config->getConfigFile()),
                    'Path:    "C:/' . $config->getGamePath() . '/' . $config->getGameAdditionalPath() . '/' . $config->getGameExe() . '" ' . $config->getGameArgs(),
                    'Version: ' . $config->getGameVersion(),
                    'Windows: ' . $config->getWindowsVersion(),
                    'Sandbox: ' . ($config->isSandbox() ? 'on' : 'off'),
                    'Sound:   ' . ($config->isPulse() ? 'pulse' : 'alsa'),
                    'CSMT:    ' . ($config->isCsmt() ? 'on' : 'off'),
                    'DXVK:    ' . ($config->isDxvk() ? 'on ' . (($dxvkVersion = $config->getDxvkVersion()) ? "({$dxvkVersion})" : '') : 'off'),
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
                    ' - Set CPU mode',
                    ' - Build',
                    ' - Reset game files',
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('settings' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $items = [
                    'Configuration *.ini files',
                ];

                $window->refresh();

                $this->windowPrint->padding(1, 1)->dotMode(false)->update($items);

            } elseif ('info' === $item['id']) {

                $window->erase()->border()->title($item['name']);

                $system = app('start')->getSystem();

                $items = [
                    'CPU:   ' . $system->getCPU(),
                    'GPU:   ' . $system->getGPU(),
                    'RAM:   ' . $system->getRAM() . ' Mb',
                    'Distr: ' . $system->getDistrName(),
                    'Linux: ' . $system->getLinuxVersion(),
                    'Glibc: ' . $system->getGlibcVersion(),
                ];

                if ($mesa = $system->getMesaVersion()) {
                    $items[] = 'Mesa:  ' . $mesa;
                }

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

            }
        };

        $this->getData()->onChangeEvent($callback);

        $this->callback = $callback;

        $callback($this->getData()->getItem());
    }

    public function pressKey($key) {}
}
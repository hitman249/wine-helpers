<?php

class WineScene extends AbstractScene {
    public function render()
    {
        /** @var Config $config */
        $config = app('start')->getConfig();
        /** @var Update $update */
        $update = app('start')->getUpdate();
        /** @var Wine $wine */
        $wine = app('start')->getWine();

        $this->window
            ->border()
            ->title($wine->version())
            ->status($update->getUrl())
            ->refresh();

        $menu = $this->renderMenu();

        $info = $this->addWidget(new InfoWidget($this->window));
        $info
            ->setData($menu)
            ->show();
    }

    public function renderMenu()
    {
        $items = [
            ['id' => 'back',            'name' => 'Back'],
            ['id' => 'winecfg',         'name' => 'Config',          'wine' => 'WINECFG'],
            ['id' => 'filemanager',     'name' => 'File Manager',    'wine' => 'WINEFILE'],
            ['id' => 'regedit',         'name' => 'Regedit',         'wine' => 'REGEDIT'],
            ['id' => 'change',          'name' => 'Change Wine version' ],
            ['id' => 'recreate_prefix', 'name' => 'Recreate prefix' ],
//            ['id' => 'taskmgr',     'name' => 'Task Manager',    'wine' => 'WINETASKMGR'],
//            ['id' => 'uninstaller', 'name' => 'Uninstaller',     'wine' => 'WINEUNINSTALLER'],
//            ['id' => 'progman',     'name' => 'Program Manager', 'wine' => 'WINEPROGRAM'],
        ];

        if (app('start')->getDriver()->isGalliumNineSupport()) {
            $items[] =  ['id' => 'galliumnine', 'name' => 'Gallium Nine' ];
        }

        $select = $this->addWidget(new PopupSelectWidget($this->window));
        $select
            ->setItems($items)
            ->border()
            ->setFullMode()
            ->maxSize(null, 16)
            ->offset(2, 1)
            ->setActive(true)
            ->show();

        $select->onEnterEvent(function ($item) {
            $config = app('start')->getConfig();

            if ('back' === $item['id']) {
                app()->showMain();
            }
            if (in_array($item['id'], ['winecfg', 'filemanager', 'regedit', 'taskmgr', 'uninstaller', 'progman'])) {
                $task   = new Task($config);
                $task
                    ->debug()
                    ->logName($item['id'])
                    ->cmd(Text::quoteArgs($config->wine($item['wine'])));

                if ('filemanager' === $item['id']) {
                    $task->run(function () {
                        (new Wine(app('start')->getConfig(), app('start')->getCommand()))->fm([]);
                    });
                } else {
                    $task->run();
                }
            }
            if ('change' === $item['id']) {
                $wineDownloader = new WineDownloader(app('start')->getConfig(), app('start')->getCommand(), app('start')->getFileSystem(), app('start')->getPack());
                $wineDownloader->wizard();
            }
            if ('recreate_prefix' === $item['id']) {
                $popup = $this->addWidget(new PopupYesNoWidget($this->getWindow()));
                $popup
                    ->setTitle('Recreate prefix')
                    ->setText([
                        'Recreate Wine prefix folder?',
                    ])
                    ->setActive(true)
                    ->show();
                $popup->onEscEvent(function () use (&$popup) { $this->removeWidget($popup->hide()); });
                $popup->onEnterEvent(function ($flag) use (&$popup, &$config) {
                    $this->removeWidget($popup->hide());

                    if (!$flag) {
                        return;
                    }

                    if (file_exists($config->getPrefixFolder())) {
                        app('start')->getFileSystem()->rm($config->getPrefixFolder());
                        app('start')->getUpdate()->restart();
                    }
                });
            }
            if ('galliumnine' === $item['id']) {
                if ($this->isGalliumNineInstalled()) {
                    $task = new Task($config);
                    $task
                        ->debug()
                        ->logName($item['id'])
                        ->cmd(Text::quoteArgs($config->wine('WINE')) . ' ninewinecfg.exe')
                        ->run();
                } else {
                    $popup = $this->addWidget(new PopupYesNoWidget($this->getWindow()));
                    $popup
                        ->setTitle('Gallium Nine')
                        ->setText([
                            'Install Gallium Nine?',
                        ])
                        ->setActive(true)
                        ->show();
                    $popup->onEscEvent(function () use (&$popup) { $this->removeWidget($popup->hide()); });
                    $popup->onEnterEvent(function ($flag) use (&$popup, &$config) {
                        $this->removeWidget($popup->hide());

                        if (!$flag) {
                            return;
                        }

                        $popup = $this->addWidget(new PopupInfoWidget($this->getWindow()));
                        $popup
                            ->setTitle('Installing Gallium Nine')
                            ->setText('Wait ...')
                            ->setActive(true)
                            ->show();

                        (new Wine($config, app('start')->getCommand()))->winetricks(['galliumnine']);

                        $this->removeWidget($popup->hide());
                    });
                }
            }
        });

        return $select;
    }

    public function pressKey($key) {}

    public function isGalliumNineInstalled()
    {
        /** @var Config $config */
        $config = app('start')->getConfig();

        return file_exists($config->getWineSystem32Folder() . '/ninewinecfg.exe');
    }
}
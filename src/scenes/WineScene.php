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
            ['id' => 'back',        'name' => 'Back'],
            ['id' => 'winecfg',     'name' => 'Config',          'wine' => 'WINECFG'],
            ['id' => 'filemanager', 'name' => 'File Manager',    'wine' => 'WINEFILE'],
            ['id' => 'regedit',     'name' => 'Regedit',         'wine' => 'REGEDIT'],
            ['id' => 'change',      'name' => 'Change Version' ],
//            ['id' => 'taskmgr',     'name' => 'Task Manager',    'wine' => 'WINETASKMGR'],
//            ['id' => 'uninstaller', 'name' => 'Uninstaller',     'wine' => 'WINEUNINSTALLER'],
//            ['id' => 'progman',     'name' => 'Program Manager', 'wine' => 'WINEPROGRAM'],
        ];

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
            if ('back' === $item['id']) {
                app()->showMain();
            }
            if (in_array($item['id'], ['winecfg', 'filemanager', 'regedit', 'taskmgr', 'uninstaller', 'progman'])) {
                $config = app('start')->getConfig();
                $task   = new Task($config);
                $task
                    ->debug()
                    ->logName($item['id'])
                    ->cmd(Text::quoteArgs($config->wine($item['wine'])))
                    ->run();
            }
            if ('change' === $item['id']) {
                $wineDownloader = new WineDownloader(app('start')->getConfig(), app('start')->getCommand(), app('start')->getFileSystem(), app('start')->getPack());
                $wineDownloader->wizard();
            }
        });

        return $select;
    }

    public function pressKey($key) {}
}
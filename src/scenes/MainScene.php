<?php

class MainScene extends AbstractScene {
    public function render()
    {
        /** @var Config $config */
        $config = app('start')->getConfig();
        /** @var Update $update */
        $update = app('start')->getUpdate();

        $this->window
            ->border()
            ->title('version: ' . $update->version())
            ->status('https://github.com/hitman249/wine-helpers')
            ->refresh();

        $menu = $this->renderMenu();

        $info = $this->addWidget(new InfoWidget($this->window));
        $info
            ->setData($menu)
            ->show();
    }

    public function renderMenu()
    {
        /** @var Config $config */
        $config = app('start')->getConfig();

        $configs = $config->findConfigsPath();

        $starts = [];

        foreach ($configs as $i => $path) {
            if ($config->getConfigFile() === $path && count($configs) === 1) {
                $starts[] = ['id' => 'start', 'config' => $config, 'name' => 'Start'];
            } else {
                $cfg = new Config($path);
                $title = 'Start → ' . $cfg->getGameTitle() .'';
                $starts[] = ['id' => 'start', 'config' => $cfg, 'name' => $title];
            }
        }

        $items = array_merge(
            $starts,
            [
                ['id' => 'wine',     'name' => 'Wine'],
                ['id' => 'tools',    'name' => 'Tools'],
                ['id' => 'settings', 'name' => 'Settings'],
                ['id' => 'info',     'name' => 'Info'],
                ['id' => 'exit',     'name' => 'Exit'],
            ]
        );

        $select = $this->addWidget(new SelectWidget($this->window));
        $select
            ->setItems($items)
            ->border()
            ->setActive(true)
            ->show();

        $select->onEnterEvent(function ($item) {
            if ($item['id'] === 'wine') {
                app()->showPrefix();
            }
            if ($item['id'] === 'exit') {
                exit(0);
            }
        });

        return $select;
    }

    public function pressKey($key) {}
}
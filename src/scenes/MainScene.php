<?php

class MainScene extends AbstractScene
{
    private $selectIndex = 0;

    public function render()
    {
        /** @var Config $config */
        $config = app('start')->getConfig();
        /** @var Update $update */
        $update = app('start')->getUpdate();

        $this->window
            ->border()
            ->title('version: ' . $update->version())
            ->status($update->getUrl())
            ->refresh();

        $menu = $this->renderMenu();

        $info = $this->addWidget(new InfoWidget($this->window));
        $info
            ->setData($menu)
            ->show();

        if ($menu->selectAt($this->selectIndex)) {
            $menu->render();
        }
        $menu->onChangeEvent(function () use (&$menu) {
            $this->selectIndex = $menu->index();
        });
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
//                ['id' => 'settings', 'name' => 'Settings'],
                ['id' => 'info',     'name' => 'Info'],
                ['id' => 'exit',     'name' => 'Exit'],
            ]
        );

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
            if ('wine' === $item['id']) {
                app()->showWine();
            }
            if ('tools' === $item['id']) {
                app()->showTools();
            }
            if ('exit' === $item['id']) {
                app()->close();
            }
        });

        return $select;
    }

    public function pressKey($key) {}
}
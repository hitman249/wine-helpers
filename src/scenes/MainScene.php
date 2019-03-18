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
                ['id' => 'tools',    'name' => 'Tools'],
                ['id' => 'wine',     'name' => 'Wine'],
                ['id' => 'config',   'name' => 'Config'],
                ['id' => 'tweaks',   'name' => 'Tweaks'],
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

        $select->onEnterEvent(function ($item, $xy) use (&$config) {
            if ('wine' === $item['id']) {
                app()->showWine();
            }
            if ('tools' === $item['id']) {
                app()->showTools();
            }
            if ('tweaks' === $item['id']) {
                app()->showTweaks();
            }
            if ('config' === $item['id']) {

                $configPaths = $config->findConfigsPath();

                if (count($configPaths) > 1) {

                    $select = $this->addWidget(new PopupSelectWidget($this->window));
                    $select
                        ->setItems(
                            array_map(
                                function ($path) {
                                    return [
                                        'id'   => $path,
                                        'name' => (new Config($path))->getGameTitle() . ' | ' . basename($path)
                                    ];
                                },
                                $configPaths
                            )
                        )
                        ->border()
                        ->setFullMode()
                        ->backAccess()
                        ->maxSize(null, 4)
                        ->offset($xy['x'], $xy['y'])
                        ->setActive(true)
                        ->show();
                    $select->onEscEvent(function () use (&$select) { $this->removeWidget($select->hide()); });
                    $select->onEnterEvent(function ($item) use (&$select) {
                        $this->removeWidget($select->hide());
                        app()->getConfigScene()->setConfig(new Config($item['id']));
                        app()->showConfig();
                    });
                } else {
                    app()->showConfig();
                }
            }
            if ('exit' === $item['id']) {
                app()->close();
            }
            if ('start' === $item['id']) {
                $select = $this->addWidget(new PopupSelectWidget($this->window));
                $select
                    ->setItems([
                        ['id' => 'start', 'name' => 'Start'],
                        ['id' => 'debug', 'name' => 'Debug'],
                        ['id' => 'fps',   'name' => 'FPS'],
                    ])
                    ->border()
                    ->setFullMode()
                    ->backAccess()
                    ->maxSize(null, 4)
                    ->offset($xy['x'], $xy['y'])
                    ->setActive(true)
                    ->show();
                $select->onEscEvent(function () use (&$select) { $this->removeWidget($select->hide()); });
                $select->onEnterEvent(function ($type) use (&$select, &$item) {
                    $this->removeWidget($select->hide());

                    /** @var Config $config */
                    $config = $item['config'];

                    $task = new Task($config);
                    $task->logName($config->getGameTitle());

                    if ('debug' === $type['id']) {
                        $task->debug();
                    }
                    if ('fps' === $type['id']) {
                        $task->fps();
                    }

                    $task->game()->run();
                });
            }
        });

        return $select;
    }

    public function pressKey($key) {}
}
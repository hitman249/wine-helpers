<?php

class TweaksScene extends AbstractScene
{
    private $selectIndex = 0;

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
            ->title('Tweaks')
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
        $items = [
            ['id' => 'back',         'name' => 'Back'],
            ['id' => 'cpu_mode',     'name' => 'CPU mode'],
            ['id' => 'dependencies', 'name' => 'Check dependencies'],
            ['id' => 'hw_info',      'name' => 'Hardware info'],
            ['id' => 'sys_info',     'name' => 'System info'],
        ];

        if (Network::isConnected()) {
            $items[] = ['id' => 'update',   'name' => 'Update'];
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

        $select->onEnterEvent(function ($item, $xy) {
            if ('back' === $item['id']) {
                app()->showMain();
            }
            if ('dependencies' === $item['id']) {
                /** @var Config $config */
                $config = app('start')->getConfig();

                /** @var Command $command */
                $command = app('start')->getCommand();

                /** @var System $system */
                $system = app('start')->getSystem();

                (new CheckDependencies($config, $command, $system))->check();

                app()->showTweaks();
            }
            if ('cpu_mode' === $item['id']) {
                $select = $this->addWidget(new PopupSelectWidget($this->window));
                $select
                    ->setTitle('Set mode')
                    ->setItems([
                        ['id' => 'performance',  'name' => 'performance'],
                        ['id' => 'ondemand',     'name' => 'ondemand (default)'],
                        ['id' => 'conservative', 'name' => 'conservative'],
                        ['id' => 'powersave',    'name' => 'powersave'],
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

                    $gui = app('gui');
                    $gui->end();
                    exec("sudo sh -c '$(for CPUFREQ in /sys/devices/system/cpu/cpu*/cpufreq/scaling_governor; do [ -f \$CPUFREQ ] || continue; echo -n {$type['id']} > \$CPUFREQ; done)'");
                    $gui->init();
                    app()->showTweaks();
                });
            }

        });

        return $select;
    }

    public function pressKey($key) {}
}
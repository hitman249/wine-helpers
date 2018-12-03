<?php

class ToolsScene extends AbstractScene {
    public function render()
    {
        /** @var Config $config */
        $config = app('start')->getConfig();
        /** @var Update $update */
        $update = app('start')->getUpdate();

        $this->window
            ->border()
            ->title('Tools')
            ->status($update->getUrl())
            ->refresh();

        $menu = $this->renderMenu();

        $info = $this->addWidget(new InfoWidget($this->window));
        $info
            ->setData($menu)
            ->show();

        $info = $this->addWidget(new PopupSelectWidget($this->window));
        $info
            ->setTitle('Select PNG file')
            ->setEndMode()
            ->setItems([
                ['id' => 'back',   'name' => 'BackBackBackBackBackBackBackBackBackBackBackBack'],
                ['id' => 'icon',   'name' => 'IconIconIconIconIconIconIconIconIconIconIcon'],
                ['id' => 'pack',   'name' => 'PackPackPackPackPackPackPackPackPackPack'],
                ['id' => 'unpack', 'name' => 'UnPackUnPackUnPackUnPackUnPackUnPackUnPackUnPackUnPackUnPackUnPackUnPackUnPack'],
                ['id' => 'build',  'name' => 'BuildBuildBuildBuildBuildBuildBuildBuildBuildBuildBuildBuild'],
                ['id' => 'reset',  'name' => 'Reset'],
                ['id' => 'back',   'name' => 'Back'],
                ['id' => 'icon',   'name' => 'Icon'],
                ['id' => 'pack',   'name' => 'Pack'],
                ['id' => 'unpack', 'name' => 'UnPack'],
                ['id' => 'build',  'name' => 'Build'],
                ['id' => 'reset',  'name' => 'Reset'],
            ])
            ->setActive(true)
            ->show();
    }

    public function renderMenu()
    {
        $items = [
            ['id' => 'back',   'name' => 'Back'],
            ['id' => 'icon',   'name' => 'Icon'],
            ['id' => 'pack',   'name' => 'Pack'],
            ['id' => 'unpack', 'name' => 'UnPack'],
            ['id' => 'build',  'name' => 'Build'],
            ['id' => 'reset',  'name' => 'Reset'],
        ];

        $select = $this->addWidget(new SelectWidget($this->window));
        $select
            ->setItems($items)
            ->border()
            ->setActive(true)
            ->show();

        $select->onEnterEvent(function ($item) {
            if ($item['id'] === 'back') {
                app()->showMain();
            }
        });

        return $select;
    }

    public function pressKey($key) {}
}
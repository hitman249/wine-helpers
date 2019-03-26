<?php

class ConfigScene extends AbstractScene
{
    private $selectIndex = 0;

    /** @var Config */
    private $config;

    /**
     * @param Config $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return null === $this->config ? app('start')->getConfig() : $this->config;
    }

    public function render()
    {
        /** @var Update $update */
        $update = app('start')->getUpdate();

        $this->window
            ->border()
            ->title('Config ~/' . basename($this->getConfig()->getConfigFile()))
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
        $config = $this->getConfig();

        $items = [
            ['id' => 'back',                            'name' => 'Back'],
            ['id' => 'config_wine_arch',                'name' => '(' . ($config->get('wine', 'WINEARCH')) . ')' . ' Wine arch'],
            ['id' => 'config_winver',                   'name' => '(' . ($config->get('script', 'winver')) . ')' . ' Windows version'],
            ['id' => 'config_patches',                  'name' => '[' . ($config->isGenerationPatchesMode() ? 'ON] ' : 'OFF]') . ' Create patches'],
            ['id' => 'config_dxvk',                     'name' => '[' . ($config->isDxvk() ? 'ON] ' : 'OFF]') . ' DXVK enable'],
            ['id' => 'config_dxvk_version',             'name' => '(' . ($config->get('script', 'dxvk_version') ?: 'latest') . ')' . ' DXVK version'],
            ['id' => 'config_esync',                    'name' => '[' . ($config->isEsync() ? 'ON] ' : 'OFF]') . ' ESYNC enable'],
            ['id' => 'config_csmt',                     'name' => '[' . ($config->getBool('script', 'csmt') ? 'ON] ' : 'OFF]') . ' CSMT enable'],
            ['id' => 'config_pulse',                    'name' => '[' . ($config->getBool('script', 'pulse') ? 'ON] ' : 'OFF]') . ' Pulse enable'],
            ['id' => 'config_sandbox',                  'name' => '[' . ($config->getBool('script', 'sandbox') ? 'ON] ' : 'OFF]') . ' Sandbox'],
            ['id' => 'config_fixres',                   'name' => '[' . ($config->getBool('script', 'fixres') ? 'ON] ' : 'OFF]') . ' Auto fixed resolution'],
            ['id' => 'config_pba',                      'name' => '[' . ($config->isPBA() ? 'ON] ' : 'OFF]') . ' PBA enable'],
            ['id' => 'config_window_enable',            'name' => '[' . ($config->getBool('window', 'enable') ? 'ON] ' : 'OFF]') . ' Window mode'],
            ['id' => 'config_faudio',                   'name' => '[' . ($config->getBool('script', 'faudio') ? 'ON] ' : 'OFF]') . ' FAudio'],
            ['id' => 'config_dumbxinputemu',            'name' => '[' . ($config->getBool('script', 'dumbxinputemu') ? 'ON] ' : 'OFF]') . ' Dumbxinputemu'],
            ['id' => 'config_dxvk_d3d10',               'name' => '[' . ($config->getBool('script', 'dxvk_d3d10') ? 'ON] ' : 'OFF]') . ' DXVK D3D10'],
            ['id' => 'config_autoupdate',               'name' => '[' . ($config->isScriptAutoupdate() ? 'ON] ' : 'OFF]') . ' SCRIPT autoupdate'],
            ['id' => 'config_dxvk_autoupdate',          'name' => '[' . ($config->isDxvkAutoupdate() ? 'ON] ' : 'OFF]') . ' DXVK autoupdate'],
            ['id' => 'config_faudio_autoupdate',        'name' => '[' . ($config->getBool('script', 'faudio_autoupdate') ? 'ON] ' : 'OFF]') . ' FAudio autoupdate'],
            ['id' => 'config_dumbxinputemu_autoupdate', 'name' => '[' . ($config->getBool('script', 'dumbxinputemu_autoupdate') ? 'ON] ' : 'OFF]') . ' Dumbxinputemu autoupdate'],


            ['id' => 'config_fix_nocrashdialog',        'name' => '[' . (!$config->getBool('fixes', 'nocrashdialog') ? 'ON] ' : 'OFF]') . ' Show crash dialog'],
            ['id' => 'config_fix_focus',                'name' => '[' . ($config->getBool('fixes', 'focus') ? 'ON] ' : 'OFF]') . ' Fix focus'],
            ['id' => 'config_fix_cfc',                  'name' => '[' . ($config->getBool('fixes', 'cfc') ? 'ON] ' : 'OFF]') . ' CheckFloatConstants'],
            ['id' => 'config_fix_glsl',                 'name' => '[' . ($config->getBool('fixes', 'glsl') ? 'ON] ' : 'OFF]') . ' Use GLSL shaders'],
            ['id' => 'config_fix_ddr',                  'name' => '(' . ($config->get('fixes', 'ddr') ?: 'default') . ')' . ' DirectDrawRenderer'],
            ['id' => 'config_fix_orm',                  'name' => '(' . ($config->get('fixes', 'orm') ?: 'default') . ')' . ' OffscreenRenderingMode'],
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

        $select->onEnterEvent(function ($item, $xy) use (&$config) {
            if ('back' === $item['id']) {
                app()->showMain();
            }

            if ('config_wine_arch' === $item['id']) {
                $select = $this->addWidget(new PopupSelectWidget($this->window));
                $select
                    ->setItems([
                        ['id' => 'win32', 'name' => 'win32'],
                        ['id' => 'win64', 'name' => 'win64'],
                    ])
                    ->border()
                    ->setFullMode()
                    ->backAccess()
                    ->maxSize(null, 4)
                    ->offset($xy['x'], $xy['y'])
                    ->setActive(true)
                    ->show();
                $select->onEscEvent(function () use (&$select) { $this->removeWidget($select->hide()); });
                $select->onEnterEvent(function ($type) use (&$select, &$config) {
                    $this->removeWidget($select->hide());
                    $config->set('wine', 'WINEARCH', $type['id']);
                    $config->save();
                    app()->showConfig();
                });
            }

            if ('config_winver' === $item['id']) {
                $select = $this->addWidget(new PopupSelectWidget($this->window));
                $select
                    ->setItems([
                        ['id' => 'win2k', 'name' => 'Windows 2000'],
                        ['id' => 'winxp', 'name' => 'Windows XP'],
                        ['id' => 'win7',  'name' => 'Windows 7'],
                        ['id' => 'win10', 'name' => 'Windows 10'],
                    ])
                    ->border()
                    ->setFullMode()
                    ->backAccess()
                    ->maxSize(null, 4)
                    ->offset($xy['x'], $xy['y'])
                    ->setActive(true)
                    ->show();
                $select->onEscEvent(function () use (&$select) { $this->removeWidget($select->hide()); });
                $select->onEnterEvent(function ($type) use (&$select, &$config) {
                    $this->removeWidget($select->hide());
                    $config->set('script', 'winver', $type['id']);
                    $config->save();
                    app()->showConfig();
                });
            }

            if ('config_patches' === $item['id']) {
                $config->set('script', 'generation_patches_mode', $config->isGenerationPatchesMode() ? 0 : 1);
                $config->save();
                app()->showConfig();
            }

            if ('config_esync' === $item['id']) {
                $config->set('export', 'WINEESYNC', $config->isEsync() ? 0 : 1);
                $config->save();
                app()->showConfig();
            }

            if ('config_pba' === $item['id']) {
                $config->set('export', 'PBA_DISABLE', $config->isPBA() ? 1 : 0);
                $config->save();
                app()->showConfig();
            }

            if ('config_dxvk' === $item['id']) {
                $config->set('script', 'dxvk', $config->isDxvk() ? 0 : 1);
                $config->save();
                app()->showConfig();
            }

            if ('config_csmt' === $item['id']) {
                $config->set('script', 'csmt', $config->getBool('script', 'csmt') ? 0 : 1);
                $config->save();
                app()->showConfig();
            }

            if ('config_pulse' === $item['id']) {
                $config->set('script', 'pulse', $config->getBool('script', 'pulse') ? 0 : 1);
                $config->save();
                app()->showConfig();
            }

            if ('config_window_enable' === $item['id']) {
                $config->set('window', 'enable', $config->getBool('window', 'enable') ? 0 : 1);
                $config->save();
                app()->showConfig();
            }

            if ('config_dxvk_d3d10' === $item['id']) {
                $config->set('script', 'dxvk_d3d10', $config->getBool('script', 'dxvk_d3d10') ? 0 : 1);
                $config->save();
                app()->showConfig();
            }

            if ('config_faudio' === $item['id']) {
                $config->set('script', 'faudio', $config->getBool('script', 'faudio') ? 0 : 1);
                $config->save();
                app()->showConfig();
            }

            if ('config_dumbxinputemu' === $item['id']) {
                $config->set('script', 'dumbxinputemu', $config->getBool('script', 'dumbxinputemu') ? 0 : 1);
                $config->save();
                app()->showConfig();
            }

            if ('config_sandbox' === $item['id']) {
                $config->set('script', 'sandbox', $config->getBool('script', 'sandbox') ? 0 : 1);
                $config->save();
                app()->showConfig();
            }

            if ('config_fixres' === $item['id']) {
                $config->set('script', 'fixres', $config->getBool('script', 'fixres') ? 0 : 1);
                $config->save();
                app()->showConfig();
            }

            if ('config_autoupdate' === $item['id']) {
                $config->set('script', 'autoupdate', $config->isScriptAutoupdate() ? 0 : 1);
                $config->save();
                app()->showConfig();
            }

            if ('config_dxvk_autoupdate' === $item['id']) {
                $config->set('script', 'dxvk_autoupdate', $config->isDxvkAutoupdate() ? 0 : 1);
                $config->save();
                app()->showConfig();
            }

            if ('config_faudio_autoupdate' === $item['id']) {
                $config->set('script', 'faudio_autoupdate', $config->getBool('script', 'faudio_autoupdate') ? 0 : 1);
                $config->save();
                app()->showConfig();
            }

            if ('config_dumbxinputemu_autoupdate' === $item['id']) {
                $config->set('script', 'dumbxinputemu_autoupdate', $config->getBool('script', 'dumbxinputemu_autoupdate') ? 0 : 1);
                $config->save();
                app()->showConfig();
            }

            if ('config_dxvk_version' === $item['id']) {

                app('start')->getUpdate()->downloadWinetricks();
                $winetricks = $config->getRootDir() . '/winetricks';

                if (file_exists($winetricks)) {

                    $versions = explode("\n", file_get_contents($winetricks));
                    $versions = array_filter($versions, function ($line) { return strpos($line, 'load_dxvk') !== false && strpos($line, 'load_dxvk()') === false; });
                    $versions = array_map(function ($line) { return str_replace('load_', '', trim($line, " \t\n\r\0\x0B(){}[].:")); }, $versions);
                    natsort($versions);
                    $versions = array_reverse($versions);
                    $versions = array_map(function ($row) { return ['id' => $row, 'name' => $row]; }, $versions);
                    $versions = array_merge([['id' => '', 'name' => 'latest']], $versions);

                    $select = $this->addWidget(new PopupSelectWidget($this->window));
                    $select
                        ->setItems($versions)
                        ->border()
                        ->setFullMode()
                        ->backAccess()
                        ->maxSize(null, 4)
                        ->offset($xy['x'], $xy['y'])
                        ->setActive(true)
                        ->show();
                    $select->onEscEvent(function () use (&$select) { $this->removeWidget($select->hide()); });
                    $select->onEnterEvent(function ($type) use (&$select, &$config) {
                        $this->removeWidget($select->hide());
                        $config->set('script', 'dxvk_version', $type['id']);
                        $config->save();
                        app()->showConfig();
                    });
                }
            }

            if ('config_fix_focus' === $item['id']) {
                $config->set('fixes', 'focus', $config->getBool('fixes', 'focus') ? 0 : 1);
                $config->save();
                app()->showConfig();
            }

            if ('config_fix_nocrashdialog' === $item['id']) {
                $config->set('fixes', 'nocrashdialog', !$config->getBool('fixes', 'nocrashdialog') ? 1 : 0);
                $config->save();
                app()->showConfig();
            }

            if ('config_fix_cfc' === $item['id']) {
                $config->set('fixes', 'cfc', $config->getBool('fixes', 'cfc') ? 0 : 1);
                $config->save();
                app()->showConfig();
            }

            if ('config_fix_glsl' === $item['id']) {
                $config->set('fixes', 'glsl', $config->getBool('fixes', 'glsl') ? 0 : 1);
                $config->save();
                app()->showConfig();
            }

            if ('config_fix_ddr' === $item['id']) {
                $select = $this->addWidget(new PopupSelectWidget($this->window));
                $select
                    ->setItems([
                        ['id' => '',       'name' => 'default'],
                        ['id' => 'gdi',    'name' => 'gdi'],
                        ['id' => 'opengl', 'name' => 'opengl'],
                    ])
                    ->border()
                    ->setFullMode()
                    ->backAccess()
                    ->maxSize(null, 4)
                    ->offset($xy['x'], $xy['y'])
                    ->setActive(true)
                    ->show();
                $select->onEscEvent(function () use (&$select) { $this->removeWidget($select->hide()); });
                $select->onEnterEvent(function ($type) use (&$select, &$config) {
                    $this->removeWidget($select->hide());
                    $config->set('fixes', 'ddr', $type['id']);
                    $config->save();
                    app()->showConfig();
                });
            }

            if ('config_fix_orm' === $item['id']) {
                $select = $this->addWidget(new PopupSelectWidget($this->window));
                $select
                    ->setItems([
                        ['id' => '',           'name' => 'default'],
                        ['id' => 'fbo',        'name' => 'fbo'],
                        ['id' => 'backbuffer', 'name' => 'backbuffer'],
                    ])
                    ->border()
                    ->setFullMode()
                    ->backAccess()
                    ->maxSize(null, 4)
                    ->offset($xy['x'], $xy['y'])
                    ->setActive(true)
                    ->show();
                $select->onEscEvent(function () use (&$select) { $this->removeWidget($select->hide()); });
                $select->onEnterEvent(function ($type) use (&$select, &$config) {
                    $this->removeWidget($select->hide());
                    $config->set('fixes', 'orm', $type['id']);
                    $config->save();
                    app()->showConfig();
                });
            }

            app('start')->getConfig()->reload();
        });

        return $select;
    }

    public function pressKey($key) {}
}
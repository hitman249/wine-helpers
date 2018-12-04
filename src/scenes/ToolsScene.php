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
    }

    public function renderMenu()
    {
        $fs = app('start')->getFileSystem();

        $items = [];
        $items[] = ['id' => 'back',   'name' => 'Back'];

        $pngs = array_map(
            function ($n) use (&$fs) {
                return ['name' => './' . $fs->relativePath($n), 'path' => $n];
            },
            app('start')->getIcon()->findPng()
        );

        if ($pngs) {
            $items[] = ['id' => 'icon',   'name' => 'Icon'];
        }

        $items[] = ['id' => 'pack',   'name' => 'Pack'];
        $items[] = ['id' => 'unpack', 'name' => 'UnPack'];
        $items[] = ['id' => 'build',  'name' => 'Build'];
        $items[] = ['id' => 'reset',  'name' => 'Reset'];

        $select = $this->addWidget(new PopupSelectWidget($this->window));
        $select
            ->setItems($items)
            ->border()
            ->setFullMode()
            ->maxSize(null, 16)
            ->offset(2, 1)
            ->setActive(true)
            ->show();

        $select->onEnterEvent(function ($item, $xy) use (&$pngs) {
            if ($item['id'] === 'back') {
                app()->showMain();
            }
            if ($item['id'] === 'icon') {
                $select = $this->addWidget(new PopupSelectWidget($this->window));
                $select
                    ->setItems([
                        ['id' => 'create', 'name' => 'Create'],
                        ['id' => 'remove', 'name' => 'Remove'],
                    ])
                    ->border()
                    ->setFullMode()
                    ->backAccess()
                    ->maxSize(null, 4)
                    ->offset($xy['x'], $xy['y'])
                    ->setActive(true)
                    ->show();

                $select->onEscEvent(function () use (&$select) {
                    $select->hide();
                    $this->removeWidget($select);
                });

                $select->onEnterEvent(function ($type) use (&$select, &$pngs) {
                    $select->hide();
                    $this->removeWidget($select);

                    if ('create' === $type['id']) {
                        $create = function ($icon) {
                            $addMenu = $this->addWidget(new PopupYesNoWidget($this->window));
                            $addMenu
                                ->setTitle('Icon Wizard')
                                ->setText('Add icon also to system menu?')
                                ->setActive(true)
                                ->show();

                            $addMenu->onEscEvent(function () use (&$addMenu) {
                                $addMenu->hide();
                                $this->removeWidget($addMenu);
                            });

                            $addMenu->onEnterEvent(function ($flag) use ($icon, &$addMenu) {
                                $addMenu->hide();
                                $this->removeWidget($addMenu);

                                $icons = app('start')->getIcon()->create($icon['path'], $flag);

                                $width = 60;

                                foreach ($icons as $icon) {
                                    if ($width < mb_strlen($icon)) {
                                        $width = mb_strlen($icon);
                                    }
                                }

                                $popup = $this->addWidget(new PopupInfoWidget($this->getWindow()));
                                $popup
                                    ->setTitle('Success')
                                    ->setText(array_merge(['Add or update icons:'], $icons))
                                    ->size($width + 2, 9)
                                    ->setButton()
                                    ->setActive(true)
                                    ->show();

                                $popup->onEnterEvent(function () use (&$popup) {
                                    $popup->hide();
                                    $this->removeWidget($popup);
                                });
                            });
                        };

                        if (count($pngs) > 1) {
                            $select = $this->addWidget(new PopupSelectWidget($this->window));
                            $select
                                ->setItems($pngs)
                                ->setTitle('Select PNG file')
                                ->setEndMode()
                                ->backAccess()
                                ->maxSize(40, 10)
                                ->setActive(true)
                                ->show();

                            $select->onEscEvent(function () use (&$select) {
                                $select->hide();
                                $this->removeWidget($select);
                            });

                            $select->onEnterEvent(function ($item) use (&$create, &$select) {
                                $select->hide();
                                $this->removeWidget($select);
                                $create($item);
                            });
                        } elseif (count($pngs) > 0) {
                            $create(reset($pngs));
                        }
                    }

                    if ('remove' === $type['id']) {
                        $icons = app('start')->getIcon()->findExistIcons();

                        if ($icons) {
                            $width = 60;

                            foreach ($icons as $icon) {
                                if ($width < mb_strlen($icon)) {
                                    $width = mb_strlen($icon);
                                }
                            }

                            $popup = $this->addWidget(new PopupYesNoWidget($this->getWindow()));
                            $popup
                                ->setTitle('Continue')
                                ->setText(array_merge(['Remove ?', 'Find icon exists:'], $icons))
                                ->size($width + 2, 10)
                                ->backAccess()
                                ->setActive(true)
                                ->show();
                            $popup->onEscEvent(function () use (&$popup) {
                                $popup->hide();
                                $this->removeWidget($popup);
                            });
                            $popup->onEnterEvent(function ($flag) use (&$popup) {
                                $popup->hide();
                                $this->removeWidget($popup);
                                if ($flag) {
                                    app('start')->getIcon()->remove();
                                }
                            });
                        } else {
                            $popup = $this->addWidget(new PopupInfoWidget($this->getWindow()));
                            $popup
                                ->setTitle('Error')
                                ->setText('Icons not found')
                                ->setButton()
                                ->backAccess()
                                ->setActive(true)
                                ->show();
                            $popup->onEscEvent(function () use (&$popup) {
                                $popup->hide();
                                $this->removeWidget($popup);
                            });
                            $popup->onEnterEvent(function () use (&$popup) {
                                $popup->hide();
                                $this->removeWidget($popup);
                            });
                        }
                    }
                });
            }
        });

        return $select;
    }

    public function pressKey($key) {}
}
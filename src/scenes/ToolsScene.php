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
        /** @var FileSystem $fs */
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

        $items[] = ['id' => 'pack',    'name' => 'Pack'];
        $items[] = ['id' => 'symlink', 'name' => 'Symlink'];
        $items[] = ['id' => 'build',   'name' => 'Build'];
        $items[] = ['id' => 'reset',   'name' => 'Reset'];

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
            if ('back' === $item['id']) {
                app()->showMain();
            }
            if ('icon' === $item['id']) {
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
                                ->setTitle('Remove?')
                                ->setText(array_merge(['Found icons:'], $icons))
                                ->size($width + 2, 9)
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

                                    $popup = $this->addWidget(new PopupInfoWidget($this->getWindow()));
                                    $popup
                                        ->setTitle('Success')
                                        ->setText('Icons removed')
                                        ->setButton()
                                        ->setActive(true)
                                        ->show();

                                    $popup->onEnterEvent(function () use (&$popup) {
                                        $popup->hide();
                                        $this->removeWidget($popup);
                                    });
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
            if ('pack' === $item['id']) {
                $select = $this->addWidget(new PopupSelectWidget($this->window));
                $select
                    ->setItems([
                        ['id' => 'pack',   'name' => 'Pack'],
                        ['id' => 'unpack', 'name' => 'UnPack'],
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

                $select->onEnterEvent(function ($type) use (&$select, &$xy) {
                    $select->hide();
                    $this->removeWidget($select);

                    $select = $this->addWidget(new PopupSelectWidget($this->window));
                    $select
                        ->setItems([
                            ['id' => 'wine', 'name' => 'Wine'],
                            ['id' => 'data', 'name' => 'Data'],
                        ])
                        ->setTitle($type['name'])
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

                    $select->onEnterEvent(function ($folder) use (&$select, &$type) {
                        $select->hide();
                        $this->removeWidget($select);

                        /** @var Config $config */
                        $config = app('start')->getConfig();
                        $path   = '';

                        if ('wine' === $folder['id']) {
                            $path = $config->getWineDir();
                        }
                        if ('data' === $folder['id']) {
                            $path = $config->getDataDir();
                        }

                        if ($path) {

                            $popup = $this->addWidget(new PopupInfoWidget($this->getWindow()));
                            $popup
                                ->setTitle($type['name'] . 'ing ' . $folder['id'])
                                ->setText('Wait...')
                                ->setActive(true)
                                ->show();

                            $status = false;

                            if ('pack' === $type['id']) {
                                $status = app('start')->getPack()->pack($path);
                            }
                            if ('unpack' === $type['id']) {
                                $status = app('start')->getPack()->unpack($path);
                            }

                            $popup->hide();
                            $this->removeWidget($popup);

                            if ($status) {
                                $popup = $this->addWidget(new PopupInfoWidget($this->getWindow()));
                                $popup
                                    ->setTitle('Success')
                                    ->setText('Success ' . $type['id'] . ' "' . $folder['id'] . '" folder')
                                    ->setButton()
                                    ->setActive(true)
                                    ->show();
                                $popup->onEnterEvent(function () use (&$popup) {
                                    $popup->hide();
                                    $this->removeWidget($popup);
                                });
                            } else {
                                $popup = $this->addWidget(new PopupInfoWidget($this->getWindow()));
                                $popup
                                    ->setTitle('Error')
                                    ->setText('Error ' . $type['id'] . ' "' . $folder['id'] . '"')
                                    ->setButton()
                                    ->setActive(true)
                                    ->show();
                                $popup->onEnterEvent(function () use (&$popup) {
                                    $popup->hide();
                                    $this->removeWidget($popup);
                                });
                            }
                        }
                    });
                });
            }
            if ('symlink' === $item['id']) {

                $folders = app('start')->getSymlink()->getDirs();

                if (!$folders) {
                    $popup = $this->addWidget(new PopupInfoWidget($this->getWindow()));
                    $popup
                        ->setTitle('Error')
                        ->setText('Not found directories in the "data" folder.')
                        ->setButton()
                        ->setActive(true)
                        ->show();
                    $popup->onEnterEvent(function () use (&$popup) {
                        $popup->hide();
                        $this->removeWidget($popup);
                    });
                } else {
                    $select = $this->addWidget(new PopupSelectWidget($this->window));
                    $select
                        ->setItems(array_map(function ($n) {return ['name' => $n];}, $folders))
                        ->border()
                        ->setTitle('Directory')
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
                    $select->onEnterEvent(function ($type) use (&$select) {
                        $select->hide();
                        $this->removeWidget($select);

                        $fs   = app('start')->getFileSystem();
                        $data = $fs->relativePath(app('start')->getConfig()->getDataDir());

                        $popup = $this->addWidget(new PopupYesNoWidget($this->window));
                        $popup
                            ->setTitle('Symlink Wizard')
                            ->setText("Replace \"./{$data}/{$type['name']}\" folder to symlink?")
                            ->setActive(true)
                            ->show();
                        $popup->onEscEvent(function () use (&$popup) {
                            $popup->hide();
                            $this->removeWidget($popup);
                        });
                        $popup->onEnterEvent(function ($flag) use (&$popup, &$type) {
                            $popup->hide();
                            $this->removeWidget($popup);

                            if ($flag) {
                                $popup = $this->addWidget(new PopupInfoWidget($this->getWindow()));
                                $popup
                                    ->setTitle('Create symlinks')
                                    ->setText('Wait...')
                                    ->setActive(true)
                                    ->show();

                                $result = app('start')->getSymlink()->replace($type['name']);

                                $popup->hide();
                                $this->removeWidget($popup);

                                $popup = $this->addWidget(new PopupInfoWidget($this->getWindow()));
                                $popup
                                    ->setTitle($result ? 'Success' : 'Error')
                                    ->setText($result ? 'Moved data' : 'Error moving')
                                    ->setButton()
                                    ->setActive(true)
                                    ->show();
                                $popup->onEnterEvent(function () use (&$popup) {
                                    $popup->hide();
                                    $this->removeWidget($popup);
                                });
                            }
                        });
                    });
                }
            }
        });

        return $select;
    }

    public function pressKey($key) {}
}
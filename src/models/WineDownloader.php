<?php

class WineDownloader
{
    private $command;
    private $config;
    private $fs;
    private $pack;
    private $isPress;
    private $result = '';

    /** @var AbstractScene */
    private $scene;

    /**
     * WineDownloader constructor.
     * @param Config $config
     * @param Command $command
     * @param FileSystem $fs
     * @param Pack $pack
     */
    public function __construct(Config $config, Command $command, FileSystem $fs, Pack $pack)
    {
        $this->command = $command;
        $this->config  = $config;
        $this->fs      = $fs;
        $this->pack    = $pack;
    }

    public function isWineEmpty()
    {
        $wine       = $this->config->getWineDir();
        $wineSquash = $this->config->getWineFile();

        if (file_exists($wineSquash) || file_exists($wine)) {
            return false;
        }

        return true;
    }

    public function wizard()
    {
        if (!app('start')->getSystem()->isXz()) {
            return false;
        }

        $this->isPress = app()->isPress();
        $this->scene = app()->getCurrentScene();

        $popup = $this->scene->addWidget(new PopupYesNoWidget($this->scene->getWindow()));
        $popup
            ->setTitle('Wine download Wizard')
            ->setText([
                'Download wine?',
            ])
            ->setActive(true)
            ->show();
        $popup->onEscEvent(function () use (&$popup) { $this->scene->removeWidget($popup->hide()); });
        $popup->onEnterEvent(function ($flag) use (&$popup) {
            $this->scene->removeWidget($popup->hide());

            if ($flag) {
                $select = $this->scene->addWidget(new PopupSelectWidget($this->scene->getWindow()));
                $select
                    ->setItems([
                        ['id' => 'kron4ek', 'name' => 'Kron4ek'],
                        ['id' => 'lutris',  'name' => 'Lutris'],
                        ['id' => 'pol',     'name' => 'PlayOnLinux'],
                    ])
                    ->setTitle('Repository')
                    ->border()
                    ->setFullMode()
                    ->backAccess()
                    ->maxSize(null, 4)
                    ->setActive(true)
                    ->show();
                $select->onEscEvent(function () use (&$select) { $this->scene->removeWidget($select->hide()); });
                $select->onEnterEvent(function ($repo) use (&$select) {
                    $this->scene->removeWidget($select->hide());

                    if ('kron4ek' === $repo['id']) {
                        $this->downloadYandexDisk('https://yadi.sk/d/IrofgqFSqHsPu/wine_builds');
                    }
                    if ('lutris' === $repo['id']) {
                        $this->downloadLutris();
                    }
                    if ('pol' === $repo['id']) {
                        $this->downloadPol();
                    }
                });
            }
        });

        if (!$this->isPress) {
            app()->press();
        }

        return true;
    }

    private function onSuccessDownload()
    {
        if ($this->result) {
            $this->clear();
            $this->extract($this->result);
        }

        app('start')->getSystem()->getUserName(true);
    }

    public function clear()
    {
        $mount = $this->pack->getMount($this->config->getWineDir());

        if ($mount && $mount->isMounted()) {
            $mount->umount();
        }

        if (file_exists($this->config->getWineFile())) {
            $this->fs->rm($this->config->getWineFile());
        }

        if (file_exists($this->config->getWineDir() . '.zip')) {
            $this->fs->rm($this->config->getWineDir() . '.zip');
        }

        if (file_exists($this->config->getWineDir())) {
            $this->fs->rm($this->config->getWineDir());
        }
    }

    public function extract($pathFile)
    {
        if (file_exists($pathFile)) {
            $popup = $this->scene->addWidget(new PopupInfoWidget($this->scene->getWindow()));
            $popup
                ->setTitle('Extract wine')
                ->setText('Wait ...')
                ->setActive(true)
                ->show();

            $result = $this->fs->unpack($pathFile, $this->config->getWineDir());

            $wine = new Wine($this->config, $this->command);
            app('start')->setWine($wine);
            app('start')->getWinePrefix()->setWine($wine);
            $this->config->updateWine();

            $this->scene->removeWidget($popup->hide());

            return $result;
        }

        return false;
    }

    public function downloadPol($_pol = null)
    {
        $popup = $this->scene->addWidget(new PopupInfoWidget($this->scene->getWindow()));
        $popup
            ->setTitle('Request')
            ->setText('Wait ...')
            ->setActive(true)
            ->show();

        $pol = (null === $_pol ? new PlayOnLinux() : $_pol);

        $items = $pol->getList();

        $this->scene->removeWidget($popup->hide());

        $this->result = '';

        $select = $this->scene->addWidget(new PopupSelectWidget($this->scene->getWindow()));
        $select
            ->setItems($items)
            ->setTitle('Select arch')
            ->border()
            ->setFullMode()
            ->backAccess()
            ->maxSize(null, 4)
            ->setActive(true)
            ->show();

        $select->onEscEvent(function () use (&$select) { $this->scene->removeWidget($select->hide()); });
        $select->onEnterEvent(function ($arch) use (&$select, &$pol) {
            $this->scene->removeWidget($select->hide());

            $popup = $this->scene->addWidget(new PopupInfoWidget($this->scene->getWindow()));
            $popup
                ->setTitle('Request')
                ->setText('Wait ...')
                ->setActive(true)
                ->show();
            $items = $pol->getList($arch);
            $this->scene->removeWidget($popup->hide());

            $select = $this->scene->addWidget(new PopupSelectWidget($this->scene->getWindow()));
            $select
                ->setItems(array_merge([['id' => '..', 'name' => '..']], $items))
                ->setTitle($arch['name'])
                ->border()
                ->setFullMode()
                ->backAccess()
                ->maxSize(null, 6)
                ->setActive(true)
                ->show();
            $select->onEscEvent(function () use (&$select) { $this->scene->removeWidget($select->hide()); });
            $select->onEnterEvent(function ($item) use (&$select, &$pol) {
                $this->scene->removeWidget($select->hide());

                if ('..' === $item['id']) {
                    $this->downloadPol($pol);
                } else {
                    if (!$this->isPress) {
                        app()->press(false);
                    }

                    $popup = $this->scene->addWidget(new PopupInfoWidget($this->scene->getWindow()));
                    $popup
                        ->setTitle('Download wine')
                        ->setText('Wait ...')
                        ->setActive(true)
                        ->show();

                    $this->result = $pol->download($item['id'], $this->config->getRootDir());

                    $this->scene->removeWidget($popup->hide());

                    $this->onSuccessDownload();
                }
            });
        });

        return $this->result;
    }

    public function downloadLutris($_lutris = null)
    {
        $popup = $this->scene->addWidget(new PopupInfoWidget($this->scene->getWindow()));
        $popup
            ->setTitle('Request')
            ->setText('Wait ...')
            ->setActive(true)
            ->show();

        $lutris = (null === $_lutris ? new Lutris() : $_lutris);

        $this->scene->removeWidget($popup->hide());

        $this->result = '';

        $select = $this->scene->addWidget(new PopupSelectWidget($this->scene->getWindow()));
        $select
            ->setItems(array_map(function ($item) { return ['id' => $item, 'name' => $item]; }, $lutris->getList()))
            ->setTitle('Select arch')
            ->border()
            ->setFullMode()
            ->backAccess()
            ->maxSize(null, 4)
            ->setActive(true)
            ->show();
        $select->onEscEvent(function () use (&$select) { $this->scene->removeWidget($select->hide()); });
        $select->onEnterEvent(function ($arch) use (&$select, &$lutris) {
            $this->scene->removeWidget($select->hide());

            $select = $this->scene->addWidget(new PopupSelectWidget($this->scene->getWindow()));
            $select
                ->setItems(
                    array_merge(
                        [['id' => '..', 'name' => '..']],
                        array_map(function ($item) { return ['id' => $item, 'name' => basename($item)]; },
                            $lutris->getList($arch['id']))
                    )
                )
                ->setTitle($arch['name'])
                ->border()
                ->setFullMode()
                ->backAccess()
                ->maxSize(null, 6)
                ->setActive(true)
                ->show();
            $select->onEscEvent(function () use (&$select) { $this->scene->removeWidget($select->hide()); });
            $select->onEnterEvent(function ($item) use (&$select, &$lutris) {
                $this->scene->removeWidget($select->hide());

                if ('..' === $item['id']) {
                    $this->downloadLutris($lutris);
                } else {
                    if (!$this->isPress) {
                        app()->press(false);
                    }

                    $popup = $this->scene->addWidget(new PopupInfoWidget($this->scene->getWindow()));
                    $popup
                        ->setTitle('Download wine')
                        ->setText('Wait ...')
                        ->setActive(true)
                        ->show();

                    $this->result = $lutris->download($item['id'], $this->config->getRootDir());

                    $this->scene->removeWidget($popup->hide());

                    $this->onSuccessDownload();
                }
            });
        });

        return $this->result;
    }

    public function downloadYandexDisk($urlOrYa, $id = null, $name = null)
    {
        $popup = $this->scene->addWidget(new PopupInfoWidget($this->scene->getWindow()));
        $popup
            ->setTitle('Request')
            ->setText('Wait ...')
            ->setActive(true)
            ->show();

        $ya = null;

        if ($urlOrYa instanceof YandexDisk) {
            if (null !== $id) {
                $ya = $urlOrYa->getFolder($id);
            } else {
                $ya = $urlOrYa;
            }
        } else {
            $ya = new YandexDisk($urlOrYa);
        }

        $this->result = '';

        $folders = [];
        $items   = [];

        if ($ya->getParent()) {
            $folders[] = ['id' => '..', 'name' => '..'];
        }

        foreach ($ya->getList() as $key => $value) {
            if (endsWith($value, '/')) {
                $folders[] = ['id' => $key, 'name' => $value];
            } else {
                $items[] = ['id' => $key, 'name' => $value];
            }
        }

        uasort($items, function ($a, $b) {
            return strcasecmp($b['name'], $a['name']);
        });

        $items = array_merge($folders, $items);

        $this->scene->removeWidget($popup->hide());

        $select = $this->scene->addWidget(new PopupSelectWidget($this->scene->getWindow()));
        $select
            ->setTitle($name ?: 'Select Wine')
            ->setItems($items)
            ->border()
            ->setFullMode()
            ->maxSize(null, 6)
            ->setActive(true)
            ->backAccess()
            ->show();
        $select->onEscEvent(function () use (&$select) { $this->scene->removeWidget($select->hide()); });
        $select->onEnterEvent(function ($item) use (&$select, &$ya) {
            $this->scene->removeWidget($select->hide());

            if ($ya->isDir($item['id'])) {
                $this->downloadYandexDisk($ya, $item['id'], $item['name']);
            } elseif ($item['id'] === '..') {
                $this->downloadYandexDisk($ya->getParent());
            } else {
                if (!$this->isPress) {
                    app()->press(false);
                }

                $popup = $this->scene->addWidget(new PopupInfoWidget($this->scene->getWindow()));
                $popup
                    ->setTitle('Download wine')
                    ->setText('Wait ...')
                    ->setActive(true)
                    ->show();

                $this->result = $ya->download($item['id'], $this->config->getRootDir());

                $this->scene->removeWidget($popup->hide());

                $this->onSuccessDownload();
            }
        });

        return $this->result;
    }
}
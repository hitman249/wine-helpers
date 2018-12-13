<?php

class GameInfo {

    private $command;
    private $config;
    private $log;
    private $buffer;
    private $created = false;

    /**
     * GameInfo constructor.
     * @param Config $config
     * @param Command $command
     */
    public function __construct(Config $config, Command $command)
    {
        if (posix_geteuid() === 0) {
            (new Logs)->log('Do not run this script as root!');
            exit(0);
        }

        $this->command = $command;
        $this->config  = $config;
    }

    public function log($text)
    {
        $logPath = $this->config->getLogsDir() . '/game_info.log';

        if (null === $this->log) {
            $this->log = app('start')->getLog();
        }

        if (null === $this->buffer) {
            $this->buffer = app('start')->getBuffer();
            $this->buffer->clear();
            if (file_exists($logPath)) {
                @unlink($logPath);
            }
        }

        $this->log->insertLogFile($text, $logPath);
        $this->buffer->add($text);
    }

    public function create()
    {
        if ($this->isEmptyGameFolder()) {

            app('gui');
            $this->created = true;

            app()->showGameInfo();

            $folders = [
                $this->config->getLogsDir(),
                $this->config->getCacheDir(),
                $this->config->getGameInfoDir(),
                $this->config->getAdditionalDir(),
                $this->config->getDataDir(),
                $this->config->getDllsDir(),
                $this->config->getDlls64Dir(),
                $this->config->getHooksDir(),
                $this->config->getHooksGpuDir(),
                $this->config->getRegsDir(),
            ];

            foreach ($folders as $path) {
                if (!file_exists($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
                }

                $this->log("Create folder \"{$path}\"");
            }

            $readme = 'readme.txt';


            /**
             * game_info/readme.txt
             */
            file_put_contents(
                $this->config->getGameInfoDir() . "/{$readme}",
                "Эта директория необходима для работы скрипта.

Описание директорий/файлов:

game_info.ini - информация об игре (обязательный файл)
data - каталог с игрой (обязательная директория)
dlls - дополнительные dll файлы (необязательная директория)
dlls64 - дополнительные dll файлы (необязательная директория)
additional - специфичные для игры настройки (необязательная директория)
hooks - скрипты которые выполняются в зависимости от каких либо событий (необязательная директория)
regs - файлы реестра windows (необязательная директория)"
            );
            $this->log('Create file   "' . $this->config->getGameInfoDir() . "/{$readme}" . '"');

            /**
             * game_info/game_info.ini
             */
            file_put_contents($this->config->getConfigFile(), $this->config->getDefaultConfig());
            $this->log('Create file   "' . $this->config->getConfigFile() . '"');

            /**
             * game_info/data/readme.txt
             */
            file_put_contents(
                $this->config->getDataDir() . "/{$readme}",
                "Здесь должна находиться игра."
            );
            $this->log('Create file   "' . $this->config->getDataDir() . "/{$readme}" . '"');


            /**
             * game_info/dlls/readme.txt
             */
            file_put_contents(
                $this->config->getDllsDir() . "/{$readme}",
                "В эту директорию нужно класть необходимые игре DLL файлы. Если таких нет
директорию можно удалить."
            );
            $this->log('Create file   "' . $this->config->getDllsDir() . "/{$readme}" . '"');


            /**
             * game_info/dlls64/readme.txt
             */
            file_put_contents(
                $this->config->getDlls64Dir() . "/{$readme}",
                "В эту директорию нужно класть необходимые игре DLL файлы. Если таких нет
директорию можно удалить."
            );
            $this->log('Create file   "' . $this->config->getDlls64Dir() . "/{$readme}" . '"');


            /**
             * game_info/regs/readme.txt
             */
            file_put_contents(
                $this->config->getRegsDir() . "/{$readme}",
                "Здесь должны находиться .reg файлы."
            );
            $this->log('Create file   "' . $this->config->getRegsDir() . "/{$readme}" . '"');


            /**
             * game_info/additional/readme.txt
             */
            file_put_contents(
                $this->config->getAdditionalDir() . "/{$readme}",
                "Специфичные для игры настройки. Класть в директории dir_1, dir_2, dir_3
и т.д. Путь для копирования (относительно drive_c) нужно указывать
в файле path.txt. Первая строчка для dir_1, вторая - для dir_2 и т.д.
Всю директорию additional можно удалить, если к игре не нужно заранее
применять настройки.

--REPLACE_WITH_USERNAME-- в файле path.txt заменяется на имя пользователя
автоматически."
            );
            $this->log('Create file   "' . $this->config->getAdditionalDir() . "/{$readme}" . '"');


            /**
             * game_info/additional/path.txt
             */
            file_put_contents(
                $this->config->getAdditionalDir() . '/path.txt',
                "users/--REPLACE_WITH_USERNAME--/Мои документы
users/--REPLACE_WITH_USERNAME--/Documents"
            );
            $this->log('Create file   "' . $this->config->getAdditionalDir() . '/path.txt' . '"');

            if (!mkdir($this->config->getAdditionalDir() . '/dir_1', 0775, true) && !is_dir($path)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
            }
            if (!mkdir($this->config->getAdditionalDir() . '/dir_2', 0775, true) && !is_dir($path)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
            }
            $this->log('Create folder "' . $this->config->getAdditionalDir() . '/dir_1' . '"');
            $this->log('Create folder "' . $this->config->getAdditionalDir() . '/dir_2' . '"');


            /**
             * game_info/additional/dir_1/readme.txt
             */
            file_put_contents(
                $this->config->getAdditionalDir() . "/dir_1/{$readme}",
                "Здесь должно находиться содержимое директории dir_1."
            );
            $this->log('Create file   "' . $this->config->getAdditionalDir() . "/dir_1/{$readme}" . '"');

            app()->getCurrentScene()->setProgress(5);

            /**
             * README.md
             */
            if ((new Update($this->config, $this->command))->updateReadme(true)) {
                $this->log('Create file   "' . $this->config->getRootDir() . '/README.md' . '"');
            }


            /**
             * game_info/hooks/after.sh
             */
            file_put_contents(
                $this->config->getHooksDir() . '/after.sh',
                '#' ."!/bin/sh\necho \"After!\""
            );
            $this->log('Create file   "' . $this->config->getHooksDir() . '/after.sh' . '"');


            /**
             * game_info/hooks/before.sh
             */
            file_put_contents(
                $this->config->getHooksDir() . '/before.sh',
                '#' ."!/bin/sh\necho \"Before!\""
            );
            $this->log('Create file   "' . $this->config->getHooksDir() . '/before.sh' . '"');


            /**
             * game_info/hooks/create.sh
             */
            file_put_contents(
                $this->config->getHooksDir() . '/create.sh',
                '#' ."!/bin/sh\necho \"Create prefix!\"\ncd ../../\n./start unlock\n./start winetricks wmp9"
            );
            $this->log('Create file   "' . $this->config->getHooksDir() . '/create.sh' . '"');


            if (!file_exists($this->config->getHooksGpuDir())) {
                if (!mkdir($this->config->getHooksGpuDir(), 0775, true) && !is_dir($this->config->getHooksGpuDir())) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->config->getHooksGpuDir()));
                }
            }
            $this->log('Create folder "' . $this->config->getHooksGpuDir() . '"');

            /**
             * game_info/hooks/gpu/amd.sh
             */
            file_put_contents(
                $this->config->getHooksGpuDir() . '/amd.sh',
                '#' ."!/bin/sh\necho \"AMD GPU hook!\""
            );
            $this->log('Create file   "' . $this->config->getHooksGpuDir() . '/amd.sh' . '"');


            /**
             * game_info/hooks/gpu/nvidia.sh
             */
            file_put_contents(
                $this->config->getHooksGpuDir() . '/nvidia.sh',
                '#' ."!/bin/sh\necho \"NVIDIA GPU hook!\""
            );
            $this->log('Create file   "' . $this->config->getHooksGpuDir() . '/nvidia.sh' . '"');


            /**
             * game_info/hooks/gpu/intel.sh
             */
            file_put_contents(
                $this->config->getHooksGpuDir() . '/intel.sh',
                '#' ."!/bin/sh\necho \"Intel GPU hook!\""
            );
            $this->log('Create file   "' . $this->config->getHooksGpuDir() . '/intel.sh' . '"');

            app()->getCurrentScene()->setProgress(10);

            $this->downloadWineWizard();
        }
    }

    private function isEmptyGameFolder()
    {
        if (!file_exists($this->config->getGameInfoDir())) {
            return true;
        }

        $skip = [$this->config->getLogsDir(), $this->config->getCacheDir(), $this->config->getConfigFile()];

        foreach (glob($this->config->getGameInfoDir() . '/*') as $path) {
            if (!in_array($path, $skip, true)) {
                return false;
            }
        }

        return true;
    }

    public function isCreated()
    {
        return $this->created;
    }

    public function selectWine($urlOrYa, $id = null, $name = null)
    {
        $scene = app()->getCurrentScene();

        $popup = $scene->addWidget(new PopupInfoWidget($scene->getWindow()));
        $popup
            ->setTitle('Request')
            ->setText('Wait ...')
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

        $result = null;

        $items = [];

        if ($ya->getParent()) {
            $items[] = ['id' => '..', 'name' => '..'];
        }

        foreach ($ya->getList() as $key => $value) {
            $items[] = ['id' => $key, 'name' => $value];
        }

        $scene->removeWidget($popup->hide());

        $select = $scene->addWidget(new PopupSelectWidget($scene->getWindow()));
        $select
            ->setTitle($name ?: 'Select Wine')
            ->setItems($items)
            ->border()
            ->setFullMode()
            ->maxSize(null, 6)
            ->setActive(true)
            ->show();
        $select->onEscEvent(function () use (&$select, &$scene) { $scene->removeWidget($select->hide()); });
        $select->onEnterEvent(function ($item) use (&$select, &$ya, &$scene, &$result) {
            $scene->removeWidget($select->hide());
            if ($ya->isDir($item['id'])) {
                $result = $this->selectWine($ya, $item['id'], $item['name']);
            } elseif ($item['id'] === '..') {
                $result = $this->selectWine($ya->getParent());
            } else {
                app()->press(false);
                $item['ya'] = $ya;
                $result = $item;

                $popup = $scene->addWidget(new PopupInfoWidget($scene->getWindow()));
                $popup
                    ->setTitle('Download wine')
                    ->setText('Wait ...')
                    ->show();

                $archive = $this->config->getRootDir() . '/archive.tar.xz';
                $ya->download($item['id'], $archive);

                $scene->removeWidget($popup->hide());

                if (file_exists($archive)) {
                    $popup = $scene->addWidget(new PopupInfoWidget($scene->getWindow()));
                    $popup
                        ->setTitle('Extract wine')
                        ->setText('Wait ...')
                        ->show();

                    app('start')->getFileSystem()->unpackXz($archive, $this->config->getWineDir());

                    $wine = new Wine($this->config, $this->command);

                    app('start')->setWine($wine);
                    app('start')->getWinePrefix()->setWine($wine);
                    $this->config->updateWine();

                    $scene->removeWidget($popup->hide());
                }
            }
        });

        return $result;
    }

    public function downloadWineWizard()
    {
        $wine       = $this->config->getWineDir();
        $wineSquash = $this->config->getWineFile();

        if (file_exists($wineSquash) && file_exists($wine) && !app('start')->getSystem()->isXz()) {
            return false;
        }

        $scene = app()->getCurrentScene();

        $popup = $scene->addWidget(new PopupYesNoWidget($scene->getWindow()));
        $popup
            ->setTitle('Wine download Wizard')
            ->setText([
                'Download wine?',
            ])
            ->setActive(true)
            ->show();
        $popup->onEscEvent(function () use (&$popup, &$scene) { $scene->removeWidget($popup->hide()); });
        $popup->onEnterEvent(function ($flag) use (&$popup, &$scene) {
            $scene->removeWidget($popup->hide());

            if ($flag) {
                $this->selectWine('https://yadi.sk/d/IrofgqFSqHsPu/wine_builds');
            }
        });
        app()->press();
    }
}
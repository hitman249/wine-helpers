<?php

class GameInfo {

    private $command;
    private $config;

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

    public function create()
    {
        if (!file_exists($this->config->getGameInfoDir())) {

            $folders = [
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
                if (!mkdir($path, 0775, true) && !is_dir($path)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
                }
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


            /**
             * game_info/game_info.ini
             */
            file_put_contents($this->config->getConfigFile(), $this->config->getDefaultConfig());


            /**
             * game_info/data/readme.txt
             */
            file_put_contents(
                $this->config->getDataDir() . "/{$readme}",
                "Здесь должна находиться игра."
            );


            /**
             * game_info/dlls/readme.txt
             */
            file_put_contents(
                $this->config->getDllsDir() . "/{$readme}",
                "В эту директорию нужно класть необходимые игре DLL файлы. Если таких нет
директорию можно удалить."
            );


            /**
             * game_info/dlls64/readme.txt
             */
            file_put_contents(
                $this->config->getDlls64Dir() . "/{$readme}",
                "В эту директорию нужно класть необходимые игре DLL файлы. Если таких нет
директорию можно удалить."
            );


            /**
             * game_info/regs/readme.txt
             */
            file_put_contents(
                $this->config->getRegsDir() . "/{$readme}",
                "Здесь должны находиться .reg файлы."
            );


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


            /**
             * game_info/additional/path.txt
             */
            file_put_contents(
                $this->config->getAdditionalDir() . '/path.txt',
                "users/--REPLACE_WITH_USERNAME--/Мои документы
users/--REPLACE_WITH_USERNAME--/Documents"
            );

            if (!mkdir($this->config->getAdditionalDir() . '/dir_1', 0775, true) && !is_dir($path)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
            }
            if (!mkdir($this->config->getAdditionalDir() . '/dir_2', 0775, true) && !is_dir($path)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
            }


            /**
             * game_info/additional/dir_1/readme.txt
             */
            file_put_contents(
                $this->config->getAdditionalDir() . "/dir_1/{$readme}",
                "Здесь должно находиться содержимое директории dir_1."
            );


            /**
             * README.md
             */
            (new Update($this->config, $this->command))->updateReadme(true);


            /**
             * game_info/hooks/after.sh
             */
            file_put_contents(
                $this->config->getHooksDir() . '/after.sh',
                '#' ."!/bin/sh\necho \"After!\""
            );


            /**
             * game_info/hooks/before.sh
             */
            file_put_contents(
                $this->config->getHooksDir() . '/before.sh',
                '#' ."!/bin/sh\necho \"Before!\""
            );


            /**
             * game_info/hooks/create.sh
             */
            file_put_contents(
                $this->config->getHooksDir() . '/create.sh',
                '#' ."!/bin/sh\necho \"Create prefix!\"\ncd ../../\n./start unlock\n./start winetricks wmp9"
            );


            if (!file_exists($this->config->getHooksGpuDir())) {
                if (!mkdir($this->config->getHooksGpuDir(), 0775, true) && !is_dir($this->config->getHooksGpuDir())) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->config->getHooksGpuDir()));
                }
            }

            /**
             * game_info/hooks/gpu/amd.sh
             */
            file_put_contents(
                $this->config->getHooksGpuDir() . '/amd.sh',
                '#' ."!/bin/sh\necho \"AMD GPU hook!\""
            );


            /**
             * game_info/hooks/gpu/nvidia.sh
             */
            file_put_contents(
                $this->config->getHooksGpuDir() . '/nvidia.sh',
                '#' ."!/bin/sh\necho \"NVIDIA GPU hook!\""
            );


            /**
             * game_info/hooks/gpu/intel.sh
             */
            file_put_contents(
                $this->config->getHooksGpuDir() . '/intel.sh',
                '#' ."!/bin/sh\necho \"Intel GPU hook!\""
            );
        }
    }
}
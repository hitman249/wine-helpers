<?php

class Icon
{
    private $config;
    private $command;
    private $system;
    private $user;
    private $home;
    private $folders;
    private $local;
    private $title;

    /**
     * Icon constructor.
     * @param Config $config
     * @param Command $command
     * @param System $system
     */
    public function __construct(Config $config, Command $command, System $system)
    {
        $this->command = $command;
        $this->config  = $config;
        $this->system  = $system;

        $this->user    = $this->system->getUserName();
        $this->home    = getenv("HOME") ?: "/home/{$this->user}";
        $this->folders = [
            "{$this->home}/Рабочий стол/Games",
            "{$this->home}/Рабочий стол/games",
            "{$this->home}/Рабочий стол/Игры",
            "{$this->home}/Рабочий стол/игры",
            "{$this->home}/Рабочий стол",
            "{$this->home}/Desktop/Игры",
            "{$this->home}/Desktop/игры",
            "{$this->home}/Desktop/Games",
            "{$this->home}/Desktop/games",
            "{$this->home}/Desktop",
        ];

        $desktop = $this->system->getDesktopPath();

        if ($desktop) {
            $this->folders = array_unique(array_merge(
                [
                    "{$desktop}/Games",
                    "{$desktop}/games",
                    "{$desktop}/Игры",
                    "{$desktop}/игры",
                    $desktop,
                ],
                $this->folders
            ));
        }

        $this->local = "{$this->home}/.local/share/applications";
        $this->title = $this->config->getGameTitle();

        if (!file_exists($this->local) && file_exists($this->home)) {
            if (!mkdir($this->local, 0775, true) && !is_dir($this->local)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->local));
            }
        }
    }

    public function create($png, $isAddMenu = true)
    {
        $icon = $this->getTemplate($png);

        if ($isAddMenu) {
            file_put_contents("{$this->local}/{$this->title}.desktop", $icon);
        }

        if ($desktop = $this->findDir()) {
            $fileName = "{$desktop}/{$this->title}";
            if (file_exists($fileName)) {
                file_put_contents($fileName, $icon);
            } else {
                file_put_contents("{$fileName}.desktop", $icon);
            }
        }
    }

    public function remove()
    {
        $icons = $this->findExistIcons();

        if (!$icons) {
            return false;
        }

        foreach ($icons as $icon) {
            @unlink($icon);
        }

        return true;
    }

    /**
     * @return array
     */
    public function findExistIcons()
    {
        $result = [];

        foreach (array_merge([$this->local], $this->folders) as $item) {
            $v1 = "{$item}/{$this->title}";
            $v2 = "{$v1}.desktop";

            if (file_exists($v1) && !is_dir($v1)) {
                $result[] = $v1;
            } elseif (file_exists($v2) && !is_dir($v2)) {
                $result[] = $v2;
            }
        }

        return $result;
    }

    /**
     * @return string
     */
    public function findDir()
    {
        foreach ($this->folders as $folder) {
            if (file_exists($folder) && is_dir($folder)) {
                return $folder;
            }
        }

        return '';
    }

    /**
     * @return array
     */
    public function findPng()
    {
        $rootDir = $this->config->getRootDir();

        $icons   = [];
        $icons[] = glob("{$rootDir}/*.png");
        $icons[] = glob("{$rootDir}/game_info/*.png");

        return array_filter(call_user_func_array('array_merge', $icons));
    }

    /**
     * @param string $png
     * @return string
     */
    public function getTemplate($png)
    {
        $rootDir = $this->config->getRootDir();

        return "[Desktop Entry]
Version=1.0
Exec={$rootDir}/start
Path={$rootDir}
Icon={$png}
Name={$this->title}
Terminal=false
TerminalOptions=
Type=Application
Categories=Game";
    }
}
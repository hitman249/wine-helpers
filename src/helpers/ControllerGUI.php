<?php

class ControllerGUI {

    private $ncurses;
    private $scenes;
    private $initPress = false;

    /**
     * ControllerGUI constructor.
     */
    public function  __construct()
    {
        $this->ncurses = new NcursesObjects\Ncurses;
        $this->init();

        $this->scenes             = [];
        $this->scenes['main']     = new MainScene();
        $this->scenes['prefix']   = new PrefixScene();
        $this->scenes['gameInfo'] = new GameInfoScene();
        $this->scenes['check']    = new CheckDependenciesScene();
        $this->scenes['tools']    = new ToolsScene();
        $this->scenes['wine']     = new WineScene();
        $this->scenes['tweaks']   = new TweaksScene();
    }

    public function init()
    {
        $this->ncurses
            ->setEchoState(false)
            ->setNewLineTranslationState(true)
            ->setCursorState(NcursesObjects\Ncurses::CURSOR_INVISIBLE)
            ->refresh();

        if (ncurses_has_colors()) {
            ncurses_start_color();
            ncurses_assume_default_colors(NCURSES_COLOR_WHITE, NCURSES_COLOR_BLUE);
        }
    }


    public function end()
    {
        ncurses_end();
    }

    public function getNcurses()
    {
        return $this->ncurses;
    }

    public function getScenes($scene = null)
    {
        return null === $scene ? $this->scenes : $this->scenes[$scene];
    }

    public function hideAll()
    {
        foreach ($this->getScenes() as $scene) {
            foreach ($scene->getWidgets() as $widget) {
                /** @var AbstractWidget $widget */
                $widget->hide();
                $widget->destruct();

            }
            $scene->hide();
            $scene->destruct();
        }
    }

    public function start()
    {
        $this->showMain();
        app('start')->getBuffer()->setSize(
            $this->getMainScene()->getWindow()->getHeight()
        );
        $this->press();
    }

    public function press($flag = true)
    {
        if (false === $flag) {
            $this->initPress = false;
            return;
        }

        if ($this->initPress === false) {
            $this->initPress = true;
            while ($this->initPress) {
                $this->pressKey(ncurses_getch());
            }
        }
    }

    public function isPress()
    {
        return $this->initPress;
    }

    public function pressKey($key)
    {
        pcntl_signal_dispatch();

        foreach ($this->getScenes() as $name => $scene) {
            if (!$scene->isActive()) {
                continue;
            }

            $pressToScene = true;
            foreach ($scene->getWidgets() as $widget) {
                /** @var AbstractWidget $widget */
                if ($widget->isActive()) {
                    if ($widget->pressKey($key) === false) {
                        return;
                    }
                    $pressToScene = false;
                }
            }

            if ($pressToScene) {
                if ($scene->pressKey($key) === false) {
                    return;
                }
            }
        }
    }

    /**
     * @return MainScene
     */
    public function getMainScene()
    {
        return $this->getScenes('main');
    }

    /**
     * @return PrefixScene
     */
    public function getPrefixScene()
    {
        return $this->getScenes('prefix');
    }

    /**
     * @return GameInfoScene
     */
    public function getGameInfoScene()
    {
        return $this->getScenes('gameInfo');
    }

    /**
     * @return GameInfoScene
     */
    public function getCheckDependenciesScene()
    {
        return $this->getScenes('check');
    }

    /**
     * @return ToolsScene
     */
    public function getToolsScene()
    {
        return $this->getScenes('tools');
    }

    /**
     * @return WineScene
     */
    public function getWineScene()
    {
        return $this->getScenes('wine');
    }

    /**
     * @return TweaksScene
     */
    public function getTweaksScene()
    {
        return $this->getScenes('tweaks');
    }

    /**
     * @return GameInfoScene|MainScene|PrefixScene|TweaksScene
     */
    public function getCurrentScene()
    {
        foreach ($this->getScenes() as $scene) {
            if ($scene->isVisible()) {
                return $scene;
            }
        }

        return $this->getMainScene();
    }

    public function showMain()
    {
        $this->hideAll();
        $this->getMainScene()->show();
    }

    public function showPrefix()
    {
        $this->hideAll();
        $this->getPrefixScene()->show();
    }

    public function showGameInfo()
    {
        $this->hideAll();
        $this->getGameInfoScene()->show();
    }

    public function showCheckDependencies()
    {
        $this->hideAll();
        $this->getCheckDependenciesScene()->show();
    }

    public function showTools()
    {
        $this->hideAll();
        $this->getToolsScene()->show();
    }

    public function showWine()
    {
        $this->hideAll();
        $this->getWineScene()->show();
    }

    public function showTweaks()
    {
        $this->hideAll();
        $this->getTweaksScene()->show();
    }

    public function close()
    {
        posix_kill(posix_getpid(), SIGINT);
        pcntl_signal_dispatch();
    }
}
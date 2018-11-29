<?php

class ControllerGUI {

    private $ncurses;
    private $scenes;

    /**
     * ControllerGUI constructor.
     */
    public function  __construct()
    {
        $this->ncurses = new NcursesObjects\Ncurses;
        $this->ncurses
            ->setEchoState(false)
            ->setNewLineTranslationState(true)
            ->setCursorState(NcursesObjects\Ncurses::CURSOR_INVISIBLE)
            ->refresh();

        if (ncurses_has_colors()) {
            ncurses_start_color();
            ncurses_assume_default_colors(NCURSES_COLOR_WHITE, NCURSES_COLOR_BLUE);
        }

        $this->scenes = [];
        $this->scenes['main']   = new MainScene();
        $this->scenes['prefix'] = new PrefixScene();
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
        $this->press();
    }

    public function press()
    {
        static $init = false;

        if ($init === false) {
            $init = true;
            while (true) {
                $this->pressKey(ncurses_getch());
            }
        }
    }

    public function pressKey($key)
    {
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
}
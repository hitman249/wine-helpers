<?php

abstract class AbstractScene {

    protected $window;
    protected $active = false;
    protected $visible = false;
    protected $widgets;
    protected $history;

    private $changeWidgetActive;

    /**
     * MainScene constructor.
     */
    public function __construct()
    {
        $this->window  = new NcursesObjects\Window;
        $this->history = new History();
        $this->widgets = [];

        $onChangeWidgetActive = function ($status, $widget) {
            if ($status) {
                $this->history->add($widget);
            } else {
                $this->history->back();
            }

            $this->updateActiveWidget();
        };

        $this->changeWidgetActive = $onChangeWidgetActive;
    }

    public function show()
    {
        app()->hideAll();
        $this->visible = true;
        $this->setActive(true);
        $this->render();

        return $this;
    }

    public function hide()
    {
        if ($this->visible) {
            $this->visible = false;
            $this->window->erase()->refresh();
        }

        return $this;
    }

    /**
     * @return \NcursesObjects\Window
     */
    public function getWindow()
    {
        return $this->window;
    }

    public function addWidget($widget)
    {
        /** @var PrintWidget|ProgressBarWidget|SelectWidget|InfoWidget|PopupYesNoWidget $widget */
        $this->widgets[] = $widget;
        $widget->onChangeActiveEvent($this->changeWidgetActive);

        return $widget;
    }

    public function removeWidget($widget)
    {
        /** @var AbstractWidget $widget */
        $widget->offChangeActiveEvent($this->changeWidgetActive);
        $this->widgets = array_filter($this->widgets, function ($item) use (&$widget) {return $item !== $widget;});
    }

    public function getWidgets()
    {
        return $this->widgets;
    }

    public function isActive()
    {
        if (!$this->isVisible()) {
            $this->setActive(false);
        }

        return $this->active;
    }

    public function setActive($flag = true)
    {
        $this->active = $flag;
        return $this;
    }

    public function isVisible()
    {
        return $this->visible;
    }

    public function setVisible($flag = true)
    {
        $this->visible = $flag;
        return $this;
    }

    private function updateActiveWidget()
    {
        $current = $this->history->current();

        foreach ($this->widgets as $widget) {
            /** @var AbstractWidget $widget */
            $widget->offChangeActiveEvent($this->changeWidgetActive);
        }

        foreach ($this->getWidgets() as $widget) {
            /** @var AbstractWidget $widget */

            if ($widget === $current) {
                $widget->setActive(true);
            } else {
                $widget->setActive(false);
            }
        }

        foreach ($this->widgets as $widget) {
            /** @var AbstractWidget $widget */
            $widget->onChangeActiveEvent($this->changeWidgetActive);
        }
    }

    abstract public function render();

    abstract public function pressKey($key);

    public function destruct() {
        $this->widgets = [];
        $this->setActive(false);
        $this->setVisible(false);
    }
}
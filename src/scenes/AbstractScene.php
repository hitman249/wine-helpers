<?php

abstract class AbstractScene {

    protected $window;
    protected $active = false;
    protected $visible = false;
    protected $widgets;

    /**
     * MainScene constructor.
     */
    public function __construct()
    {
        $this->window  = new NcursesObjects\Window;
        $this->widgets = [];
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

    public function addWidget($widget)
    {
        $this->widgets[] = $widget;
        return $widget;
    }

    public function removeWidget($widget)
    {
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

    abstract public function render();

    abstract public function pressKey($key);

    public function destruct() {
        $this->widgets = [];
        $this->setActive(false);
        $this->setVisible(false);
    }
}
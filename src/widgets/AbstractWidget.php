<?php

abstract class AbstractWidget {

    /** @var \NcursesObjects\Window  */
    protected $parentWindow;
    /** @var \NcursesObjects\Window  */
    protected $window;
    protected $active = false;
    protected $visible = false;
    protected $change;
    protected $changeActive;
    protected $enter;
    protected $esc;
    protected $back = false;

    /**
     * AbstractWidget constructor.
     * @param \NcursesObjects\Window $window
     */
    public function __construct($window)
    {
        $this->parentWindow = $window;
        $this->change       = [];
        $this->changeActive = [];
        $this->enter        = [];
        $this->esc          = [];
    }

    public function getWindow()
    {
        return $this->window;
    }

    public function getParentWindow()
    {
        return $this->parentWindow;
    }

    public function isActive()
    {
        if (!$this->isVisible()) {
            $this->active = false;
        }

        return $this->active;
    }

    public function setActive($flag = true)
    {
        $this->active = $flag;
        $this->doChangeActiveEvent($this->active, $this);

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

    public function backAccess($flag = true)
    {
        $this->back = $flag;
        return $this;
    }

    public function refresh()
    {
        $this->getWindow()->reload();
        return $this;
    }

    public function show()
    {
        $this->visible = true;
        $this->render();

        return $this;
    }

    public function hide()
    {
        if ($this->visible) {
            $this->setVisible(false);
            $this->setActive(false);
            $this->getParentWindow()->reload();
            foreach (app()->getCurrentScene()->getWidgets() as $widget) {
                /** @var AbstractWidget $widget */
                if ($widget->isVisible()) {
                    $widget->refresh();
                }
            }
        }

        return $this;
    }

    public function onChangeEvent($callback)
    {
        $this->change[] = $callback;
    }

    public function offChangeEvent($callback)
    {
        $this->change = array_filter($this->change, function ($item) use (&$callback) {return $item !== $callback;});
    }

    protected function doChangeEvent($v1, $v2 = null, $v3 = null)
    {
        foreach ($this->change as $change) {
            $change($v1, $v2, $v3);
        }
    }

    public function onEnterEvent($callback)
    {
        $this->enter[] = $callback;
    }

    public function offEnterEvent($callback)
    {
        $this->enter = array_filter($this->enter, function ($item) use (&$callback) {return $item !== $callback;});
    }

    protected function doEnterEvent($v1 = null, $v2 = null, $v3 = null)
    {
        foreach ($this->enter as $enter) {
            $enter($v1, $v2, $v3);
        }
    }

    public function onEscEvent($callback)
    {
        $this->esc[] = $callback;
    }

    public function offEscEvent($callback)
    {
        $this->esc = array_filter($this->esc, function ($item) use (&$callback) {return $item !== $callback;});
    }

    protected function doEscEvent($v1 = null, $v2 = null, $v3 = null)
    {
        foreach ($this->esc as $esc) {
            $esc($v1, $v2, $v3);
        }
    }

    public function onChangeActiveEvent($callback)
    {
        $this->changeActive[] = $callback;
    }

    public function offChangeActiveEvent($callback)
    {
        $this->changeActive = array_filter($this->changeActive, function ($item) use (&$callback) {return $item !== $callback;});
    }

    protected function doChangeActiveEvent($v1, $v2 = null, $v3 = null)
    {
        foreach ($this->changeActive as $event) {
            $event($v1, $v2, $v3);
        }
    }

    abstract public function pressKey($key);

    abstract public function render();

    public function destruct() {
        $this->change = [];
        $this->enter = [];
        $this->setActive(false);
        $this->setVisible(false);
    }
}
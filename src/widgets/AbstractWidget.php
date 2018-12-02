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
            $this->getWindow()->erase()->refresh();
            $this->getParentWindow()->refresh();
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

    protected function doEnterEvent($v1, $v2 = null, $v3 = null)
    {
        foreach ($this->enter as $enter) {
            $enter($v1, $v2, $v3);
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
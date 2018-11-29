<?php

abstract class AbstractWidget {

    protected $active = false;
    protected $visible = false;
    protected $window;
    protected $change;
    protected $enter;

    /**
     * AbstractWidget constructor.
     * @param \NcursesObjects\Window $window
     */
    public function __construct($window)
    {
        $this->window = $window;
        $this->change = [];
        $this->enter  = [];
    }

    public function getWindow()
    {
        return $this->window;
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

    public function show()
    {
        $this->visible = true;
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

    public function onChangeEvent($callback)
    {
        $this->change[] = $callback;
    }

    public function onEnterEvent($callback)
    {
        $this->enter[] = $callback;
    }

    public function removeChangeEvent($callback)
    {
        $this->change = array_filter($this->change, function ($item) use (&$callback) {return $item !== $callback;});
    }

    public function removeEnterEvent($callback)
    {
        $this->enter = array_filter($this->enter, function ($item) use (&$callback) {return $item !== $callback;});

    }

    protected function doChangeEvent($v1, $v2 = null, $v3 = null)
    {
        foreach ($this->change as $change) {
            $change($v1, $v2, $v3);
        }
    }

    protected function doEnterEvent($v1, $v2 = null, $v3 = null)
    {
        foreach ($this->enter as $enter) {
            $enter($v1, $v2, $v3);
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
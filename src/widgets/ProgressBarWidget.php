<?php

class ProgressBarWidget extends AbstractWidget {

    private $percent = 0;
    private $offset = 0;
    private $padding = 0;

    public function pressKey($key) {}

    public function init()
    {
        if (null === $this->window) {
            $this->getParentWindow()->getSize($columns, $row);
            $this->window = new \NcursesObjects\Window($columns - ($this->offset + $this->padding * 2), 1, $this->offset + $this->padding, $row - 1);
        }

        return $this;
    }

    public function offset($offset, $padding)
    {
        $this->offset  = $offset;
        $this->padding = $padding;

        return $this;
    }

    public function setProgress($percent)
    {
        $this->percent = $percent >= 0 && $percent <= 100 ? $percent : ($percent > 100 ? 100 : 0);
        return $this;
    }

    public function render()
    {
        $this->init();

        $this->window->getSize($columns, $row);
        $symbols = round(($columns - 2)  / 100 * $this->percent);

        $this->window->erase()->refresh();
        $this->window->drawStringHere('[' . str_repeat('=', $symbols) . str_repeat('-', $columns - 2 - $symbols) . ']');
        $this->window->refresh();
    }
}
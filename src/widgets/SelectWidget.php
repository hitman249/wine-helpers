<?php

class SelectWidget extends AbstractWidget
{
    private $windowBorder = false;
    private $index = 0;
    private $items;
    private $width;
    private $height;
    private $x = 2;
    private $y = 1;

    public function init()
    {
        if (null === $this->window) {

            $width = 0;

            foreach ($this->items as $item) {
                if ($width < ($len = mb_strlen($item['name']))) {
                    $width = $len;
                }
            }

            $width += 5;
            $width = ($width > 20 ? $width : 20);

            $this->window = new \NcursesObjects\Window($width, count($this->items) + 2, $this->x, $this->y);
        }

        if ($this->windowBorder) {
            $this->window->border();
        }
    }

    public function border($flag = true)
    {
        $this->windowBorder = $flag;

        return $this;
    }

    public function size($width, $height)
    {
        $this->width  = $width;
        $this->height = $height;

        return $this;
    }

    public function getWidth()
    {
        $this->window->getSize($width, $height);
        return $this->windowBorder ? $width + 2 : $width;
    }

    public function getHeight()
    {
        $this->window->getSize($width, $height);
        return $this->windowBorder ? $height + 2 : $height;
    }

    public function offset($x, $y)
    {
        $this->x = $x;
        $this->y = $y;

        return $this;
    }

    public function setItems($items)
    {
        $this->items = $items;
        return $this;
    }

    public function render()
    {
        $this->init();

        foreach ($this->items as $i => $item) {
            if ($this->index === $i) {
                $this->window->moveCursor(2, 1 + $i)->drawStringHere($item['name'], NCURSES_A_REVERSE);
            } else {
                $this->window->moveCursor(2, 1 + $i)->drawStringHere($item['name']);
            }
        }

        $this->window->refresh();
    }

    public function selectAt($index)
    {
        if (count($this->items) <= $index || 0 > $index) {
            return false;
        }

        $this->index = $index;

        $this->doChangeEvent($this->getItem());

        return true;
    }

    public function itemAt($index)
    {
        if (count($this->items) <= $index || 0 > $index) {
            return reset($this->items);
        }

        return $this->items[$this->index];
    }

    public function getItem()
    {
        return $this->itemAt($this->index);
    }

    public function selectNext()
    {
        if ($this->selectAt($this->index + 1)) {
            $this->render();
            return false;
        }

        return false;
    }

    public function selectPrev()
    {
        if ($this->selectAt($this->index - 1)) {
            $this->render();
            return false;
        }

        return false;
    }

    public function pressKey($key)
    {
        if (\NcursesObjects\Keys::KEY_DOWN === $key) {
            $this->selectNext();
        }

        if (\NcursesObjects\Keys::KEY_UP === $key) {
            $this->selectPrev();
        }

        if (\NcursesObjects\Keys::KEY_ENTER === $key) {
            $this->doEnterEvent($this->getItem());
            return false;
        }
    }
}
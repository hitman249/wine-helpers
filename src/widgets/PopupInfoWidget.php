<?php

class PopupInfoWidget extends AbstractWidget {

    private $status = true;
    private $title  = '';
    private $text   = '';
    private $button = '';
    private $width  = 60;
    private $height = 8;

    public function init()
    {
        if (null === $this->window) {
            $this->window = \NcursesObjects\Window::createCenteredOf($this->getParentWindow(), $this->width, $this->height);
        }

        return $this;
    }

    public function size($width, $height)
    {
        $this->width  = $width;
        $this->height = $height;

        return $this;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function setText($text)
    {
        $this->text = $text;
        return $this;
    }

    public function setButton($title = ' Ok ')
    {
        $this->button = $title;
        return $this;
    }

    public function render()
    {
        $this->init();

        $this->window->border();

        if ($this->title) {
            $this->window->title($this->title);
        }

        if ($this->text) {
            foreach ((array)$this->text as $i => $line) {
                $this->window->moveCursor(2, $i + 2)->drawStringHere($line);
            }
        }

        if ($this->button) {
            $buttonLen = mb_strlen($this->button);
            $width     = $this->window->getWidth();
            $position  = ($width / 2) - ($buttonLen / 2);
            $this->window->moveCursor($position, $this->height - 3)->drawStringHere($this->button, NCURSES_A_REVERSE);
        }

        $this->window->refresh();
    }

    public function pressKey($key)
    {
        if (($this->button && \NcursesObjects\Keys::KEY_ENTER === $key) || !$this->button) {
            $this->hide();
        }
    }
}
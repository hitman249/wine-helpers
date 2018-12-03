<?php

class PopupInfoWidget extends AbstractWidget {

    private $status = true;
    private $title  = '';
    private $text   = '';

    public function init()
    {
        if (null === $this->window) {
            $this->window = \NcursesObjects\Window::createCenteredOf($this->getParentWindow(), 60, 8);
        }

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

    public function render()
    {
        $this->init();

        $this->window->border()->title($this->title);

        $this->window->moveCursor(2, 2)->drawStringHere($this->text);

        $this->window->refresh();
    }

    public function pressKey($key)
    {
        $this->hide();
    }
}
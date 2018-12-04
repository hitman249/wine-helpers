<?php

class PopupYesNoWidget extends AbstractWidget {

    private $status = true;
    private $title  = 'Continue';
    private $text   = '';
    private $yes    = ' Yes ';
    private $no     = ' No ';
    private $width  = 60;
    private $height = 8;

    public function init()
    {
        if (null === $this->window) {
            $this->window = \NcursesObjects\Window::createCenteredOf($this->getParentWindow(), $this->width, $this->height);
        }

        return $this;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function setYes($title)
    {
        $this->yes = $title;
        return $this;
    }


    public function setNo($title)
    {
        $this->no = $title;
        return $this;
    }

    public function setText($text)
    {
        $this->text = $text;
        return $this;
    }

    public function size($width, $height)
    {
        $this->width  = $width;
        $this->height = $height;

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

        $yesLen = mb_strlen($this->yes);
        $noLen  = mb_strlen($this->no);
        $width  = $this->window->getWidth();
        $space  = 6;

        $all = $yesLen + $noLen + $space;
        $yes = ($width / 2) - ($all / 2);
        $no  = $yes + $yesLen + $space;

        $this->window->moveCursor($yes, $this->height - 3)->drawStringHere($this->yes, $this->status ? NCURSES_A_REVERSE : null);
        $this->window->moveCursor($no, $this->height - 3)->drawStringHere($this->no, !$this->status ? NCURSES_A_REVERSE : null);

        $this->window->refresh();
    }

    public function pressKey($key)
    {
        if (\NcursesObjects\Keys::KEY_RIGHT === $key) {
            $this->status = false;
            $this->render();
        }

        if (\NcursesObjects\Keys::KEY_LEFT === $key) {
            $this->status = true;
            $this->render();
        }

        if (\NcursesObjects\Keys::KEY_ENTER === $key) {
            $this->hide();
            $this->doEnterEvent($this->status);
            return false;
        }
    }
}
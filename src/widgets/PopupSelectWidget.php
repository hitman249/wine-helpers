<?php

class PopupSelectWidget extends AbstractWidget {

    private $title;
    private $items = [];
    private $index = 0;
    private $mode = 'start';
    private $height;
    private $width;

    public function init()
    {
        if (null === $this->window) {
            $this->window = \NcursesObjects\Window::createCenteredOf($this->getParentWindow(), 50, 10);
            $this->height = 8;
            $this->width  = 44;
        }
    }

    public function setItems($items)
    {
        $this->items = $items;
        return $this;
    }

    public function setEndMode()
    {
        $this->mode = 'end';
        return $this;
    }

    public function setStartMode()
    {
        $this->mode = 'start';
        return $this;
    }
    
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    private function getHeight()
    {
        if (!$this->title) {
            return $this->height;
        }

        return $this->height - 1;
    }

    private function titleItem($title)
    {
        $len = mb_strlen($title);

        if ($len <= $this->width) {
            return $title . str_repeat(' ', $this->width - $len);
        }

        if ($this->mode === 'end') {
            return '..' . mb_substr($title, - ($this->width - 2));
        }

        return mb_substr($title, 0, $this->width - 2) . '..';
    }

    public function items()
    {
        $offset    = (int)(($this->index + 1) - ($this->getHeight() / 2));
        $offset    = $offset < 0 ? 0 : $offset;
        $maxOffset = count($this->items) - $this->getHeight();
        $offset    = $offset > $maxOffset ? $maxOffset : $offset;
        $offset    = $offset < 0 ? 0 : $offset;

        return array_slice($this->items, $offset, $this->getHeight(), true);
    }

    public function render()
    {
        $this->init();

        $this->window->erase()->border();

        if ($this->title) {
            $this->window->title($this->title);
        }


        $ip = 0;
        $progress = $this->getProgressBar();

        $ii = 0;
        if ($this->title) {
            $ii = 1;
        }
        foreach ($this->items() as $i => $item) {
            $this->window->moveCursor($this->width + 3, 1 + $ii)->drawStringHere($progress[$ip]);
            $ip++;

            if ($this->index === $i) {
                $this->window->moveCursor(2, 1 + $ii)->drawStringHere($this->titleItem($item['name']), NCURSES_A_REVERSE);
            } else {
                $this->window->moveCursor(2, 1 + $ii)->drawStringHere($this->titleItem($item['name']));
            }
            $ii++;
        }

        $this->window->refresh();
    }

    public function getProgressBar()
    {
        $result = [];

        $onePercent     = 100 / $this->getHeight();
        $currentPercent = count($this->items) > 0 ? (100 / (count($this->items) / ($this->index + 1))) : 0;

        if ($currentPercent < $onePercent) {
            $result[] = '┃';
            for ($i = 0, $end = $this->getHeight() - 1; $i < $end; $i++) {
                $result[] = '│';
            }

        } elseif ($currentPercent >= (100 - $onePercent)) {
            for ($i = 0, $end = $this->getHeight() - 1; $i < $end; $i++) {
                $result[] = '│';
            }
            $result[] = '┃';
        } else {
            $i1 = ($currentPercent / $onePercent) -1;
            for ($i = 0, $end = $i1; $i < $end; $i++) {
                $result[] = '│';
            }
            $result[] = '┃';
            for ($i = 0, $end = $this->getHeight() - 1 - $i1; $i < $end; $i++) {
                $result[] = '│';
            }
        }

        return $result;
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
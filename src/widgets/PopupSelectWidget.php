<?php

class PopupSelectWidget extends AbstractWidget {

    private $title;
    private $items = [];
    private $index = 0;
    private $mode = 'start';
    private $columns = 50;
    private $rows = 10;
    private $height;
    private $width;
    private $x;
    private $y;
    private $border = true;
    private $progress = false;
    private $maxColumns;
    private $maxRows;
    private $activeY;
    private $activeX;

    public function init()
    {
        if (null === $this->window) {
            if ('full' === $this->mode) {
                $width = 0;

                foreach ($this->items as $item) {
                    if ($width < ($len = mb_strlen($item['name']))) {
                        $width = $len;
                    }
                }

                $width += 5;
                $width = ($width > 20 ? $width : 20);

                $this->columns = $width;
            }

            if (null !== $this->maxColumns && $this->maxColumns < $this->columns) {
                $this->columns = $this->maxColumns;
            }

            if (null !== $this->maxRows && $this->maxRows > count($this->items)) {
                $this->rows = count($this->items) + 2 + ($this->title ? 1 : 0);
            }

            $this->progress = count($this->items) > $this->getInnerHeight();

            if (null !== $this->x && null !== $this->y) {
                $this->window = new \NcursesObjects\Window($this->columns - ($this->progress ? 0 : 1), $this->rows, $this->x, $this->y);
            } else {
                $this->window = \NcursesObjects\Window::createCenteredOf($this->getParentWindow(), $this->columns - ($this->progress ? 0 : 1), $this->rows);
            }

            $this->width  = $this->window->getWidth()  - 5;
            $this->height = $this->window->getHeight() - 2;
        }
    }

    public function border($flag = true)
    {
        $this->border = $flag;
        return $this;
    }

    public function setItems($items)
    {
        $this->items = $items;
        return $this;
    }

    public function setFullMode()
    {
        $this->mode = 'full';
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

    public function size($width, $height)
    {
        $this->columns = $width;
        $this->rows    = $height;

        return $this;
    }

    public function maxSize($width, $height)
    {
        $this->maxColumns = $width;
        $this->maxRows    = $height;

        return $this;
    }

    public function offset($x, $y)
    {
        $this->x = $x;
        $this->y = $y;

        return $this;
    }

    public function getHeight()
    {
        return $this->window->getHeight() + 2;
    }

    public function getWidth()
    {
        return $this->window->getWidth() + 2;
    }

    private function getInnerHeight()
    {
        if (!$this->title) {
            return $this->rows - 2;
        }

        return $this->rows - 3;
    }

    private function getInnerWidth()
    {
        return $this->width;
    }

    private function titleItem($title)
    {
        $len = mb_strlen($title);

        $width = ($this->getInnerWidth() + ('full' === $this->mode ? 1 : 0));

        if ($len <= $width) {
            return $title . str_repeat(' ', $width - $len);
        }

        if ($this->mode === 'end') {
            return '..' . mb_substr($title, - ($this->getInnerWidth() - 2));
        }

        return mb_substr($title, 0, $this->getInnerWidth() - 2) . '..';
    }

    private function items()
    {
        $offset    = (int)(($this->index + 1) - ($this->getInnerHeight() / 2));
        $offset    = $offset < 0 ? 0 : $offset;
        $maxOffset = count($this->items) - $this->getInnerHeight();
        $offset    = $offset > $maxOffset ? $maxOffset : $offset;
        $offset    = $offset < 0 ? 0 : $offset;

        return array_slice($this->items, $offset, $this->getInnerHeight(), true);
    }

    public function render()
    {
        $this->init();

        $this->window->erase();

        if ($this->border) {
            $this->window->border();
        }

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
            if ($this->progress) {
                $this->window->moveCursor($this->getInnerWidth() + 3, 1 + $ii)->drawStringHere($progress[$ip]);
                $ip++;
            }

            if ($this->index === $i) {
                $this->activeX = $this->getWindow()->getX() + $this->getWindow()->getWidth();
                $this->activeY = $this->getWindow()->getY() + $ii;
                $this->window->moveCursor(2, 1 + $ii)->drawStringHere($this->titleItem($item['name']), NCURSES_A_REVERSE);
            } else {
                $this->window->moveCursor(2, 1 + $ii)->drawStringHere($this->titleItem($item['name']));
            }
            $ii++;
        }

        $this->window->refresh();
    }

    private function getProgressBar()
    {
        $result = [];

        $onePercent     = 100 / $this->getInnerHeight();
        $currentPercent = count($this->items) > 0 ? (100 / (count($this->items) / ($this->index + 1))) : 0;

        if ($currentPercent < $onePercent) {
            $result[] = '┃';
            for ($i = 0, $end = $this->getInnerHeight() - 1; $i < $end; $i++) {
                $result[] = '│';
            }

        } elseif ($currentPercent >= (100 - $onePercent)) {
            for ($i = 0, $end = $this->getInnerHeight() - 1; $i < $end; $i++) {
                $result[] = '│';
            }
            $result[] = '┃';
        } else {
            $i1 = ($currentPercent / $onePercent) -1;
            for ($i = 0, $end = $i1; $i < $end; $i++) {
                $result[] = '│';
            }
            $result[] = '┃';
            for ($i = 0, $end = $this->getInnerHeight() - 1 - $i1; $i < $end; $i++) {
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

    public function index()
    {
        return $this->index;
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

        if ($this->back && \NcursesObjects\Keys::KEY_ESC == $key) {
            $this->doEscEvent();
        }

        if (\NcursesObjects\Keys::KEY_ENTER === $key) {
            $this->doEnterEvent($this->getItem(), ['x' => $this->activeX, 'y' => $this->activeY]);
            return false;
        }
    }
}
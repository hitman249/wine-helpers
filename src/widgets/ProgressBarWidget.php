<?php

class ProgressBarWidget extends AbstractWidget {

    /** @var \NcursesObjects\Window */
    private $progressWindow;
    private $percent = 0;
    private $offset = 0;
    private $padding = 0;

    public function pressKey($key) {}

    public function init()
    {
        if (null === $this->progressWindow) {
            $this->window->getSize($columns, $row);
            $this->progressWindow = new \NcursesObjects\Window($columns - ($this->offset + $this->padding * 2), 1, $this->offset + $this->padding, $row - 1);
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
        $this->percent = $percent;
        return $this;
    }

    public function render()
    {
        $this->init();

        $this->progressWindow->getSize($columns, $row);
        $symbols = round($columns / 100 * $this->percent);

        $this->progressWindow->drawStringHere(str_repeat('▓', $symbols) . str_repeat('░', $columns - $symbols));
        $this->progressWindow->refresh();
    }
}
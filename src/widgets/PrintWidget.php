<?php

class PrintWidget extends AbstractWidget
{
    /** @var \NcursesObjects\Window */
    private $printWindow;
    private $text     = '';
    private $paddingX = 1;
    private $paddingY = 0;
    private $dotMode  = false;

    public function init()
    {
        if (null === $this->printWindow) {
            $this->printWindow = new \NcursesObjects\Window(
                $this->window->getWidth() - 2,
                $this->window->getHeight() - 2 ,
                $this->window->getX() + 1,
                $this->window->getY() + 1
            );
        }
    }

    public function pressKey($key) {}

    public function render() {
        $i = 0;

        for (; $i < $this->paddingY; $i++) {
            $this->printWindow->moveCursor($this->paddingX + 0, $i++)->drawStringHere('');
        }
        $i--;
        foreach (is_array($this->text) ? $this->text : explode("\n", $this->text) as $line) {
            $this->printWindow->moveCursor($this->paddingX + 0, $i++)->drawStringHere($line);
        }

        $this->printWindow->refresh();
    }

    public function padding($left, $top = 0)
    {
        $this->paddingX = $left;
        $this->paddingY = $top;

        return $this;
    }

    public function dotMode($flag = true)
    {
        $this->dotMode = $flag;
        return $this;
    }

    public function update($text)
    {
        $buffer = [];

        $this->init();

        $width = $this->printWindow->getWidth() - ($this->paddingX * 2);
        foreach (is_array($text) ? $text : explode("\n", $text) as $line) {
            foreach (explode("\n", $line) as $line1) {

                $str = str_split($line1, $width);

                if ($this->dotMode && count($str) > 1) {
                    $buffer[] = mb_substr($line1, 0, $width - 3) . '...';
                } else {
                    foreach ($str as $line2) {
                        $buffer[] = $line2;
                    }
                }
            }
        }

        $this->text = $buffer;
        $this->render();

        return $this;
    }
}
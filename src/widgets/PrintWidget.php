<?php

class PrintWidget extends AbstractWidget
{
    private $text = '';
    private $paddingLeft = 1;
    private $paddingRight = 1;
    private $paddingTop = 0;
    private $paddingBottom = 0;
    private $dotMode = false;

    public function init()
    {
        if (null === $this->window) {
            $this->window = new \NcursesObjects\Window(
                $this->getParentWindow()->getWidth() - 2,
                $this->getParentWindow()->getHeight() - 2 ,
                $this->getParentWindow()->getX() + 1,
                $this->getParentWindow()->getY() + 1
            );
        }
    }

    public function pressKey($key) {}

    public function render() {
        $this->init();

        $this->window->erase();

        $i = 0;

        for (; $i < $this->paddingTop; $i++) {
            $this->window->moveCursor($this->paddingLeft + 0, $i++)->drawStringHere('');
        }
        if ($this->paddingTop > 0) {
            $i--;
        }
        foreach (is_array($this->text) ? $this->text : explode("\n", $this->text) as $line) {
            $this->window->moveCursor($this->paddingLeft + 0, $i++)->drawStringHere($line);
        }

        $this->window->refresh();
    }

    public function padding($left, $top = 0, $bottom = 0, $right = null)
    {
        $this->paddingLeft   = $left;
        $this->paddingTop    = $top;
        $this->paddingBottom = $bottom;
        $this->paddingRight  = null === $right ? $left : $right;

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

        $width = $this->window->getWidth() - ($this->paddingLeft + $this->paddingRight);
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

        $this->text = array_slice($buffer, -($this->window->getHeight() - $this->paddingBottom));
        $this->render();

        return $this;
    }
}
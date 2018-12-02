<?php

class Buffer {

    private $size = 150;
    private $buffer;
    private $changes;

    /**
     * Buffer constructor.
     */
    public function __construct()
    {
        $this->buffer  = [];
        $this->changes = [];
    }

    public function setSize($size)
    {
        $this->size = $size;
    }

    public function add($text)
    {
        if (count($this->buffer) <= $this->size) {
            $this->buffer[] = $text;
        } else {
            array_shift($this->buffer);
            $this->buffer[] = $text;
        }

        $this->doChange();
    }

    private function doChange() {
        foreach ($this->changes as $change) {
            if ($change) {
                $change($this->buffer);
            }
        }
    }

    public function onChangeEvent($callable)
    {
        $this->changes[] = $callable;
    }

    public function offChangeEvent($callable)
    {
        $this->changes = array_filter($this->changes, function ($item) use (&$callable) {return $item !== $callable;});
    }

    public function clear()
    {
        $this->buffer = [];
    }
}
<?php

class History
{
    private $history;

    /**
     * History constructor.
     */
    public function __construct()
    {
        $this->history = [];
    }

    public function add(&$object)
    {
        $this->history[] = $object;
    }

    public function back()
    {
        array_pop($this->history);
        return $this->current();
    }

    public function current()
    {
        return end($this->history);
    }

    public function count()
    {
        return count($this->history);
    }
}
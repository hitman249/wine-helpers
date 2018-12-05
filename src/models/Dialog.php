<?php

class Dialog
{
    private $type;
    private $title;
    private $width;
    private $height;
    private $items;
    private $text;
    private $columns;

    /**
     * Dialog constructor.
     */
    public function __construct()
    {
        $this->type    = '--info';
        $this->width   = '';
        $this->height  = '';
        $this->title   = '--title=""';
        $this->items   = [];
    }

    public function typeInfo()
    {
        $this->type = '--info';
        return $this;
    }

    public function typeWarning()
    {
        $this->type = '--warning';
        return $this;
    }

    public function typeList()
    {
        $this->type = '--list';
        return $this;
    }
    public function typeQuestion()
    {
        $this->type = '--question';
        return $this;
    }

    public function title($title)
    {
        $this->title = "--title=\"{$title}\"";;
        return $this;
    }

    public function text($text)
    {
        $this->text = "--text=\"{$text}\"";
        return $this;
    }

    public function size($width = null, $height = null)
    {
        $this->width  = $width ? "--width={$width}" : '';
        $this->height = $height ? "--height={$height}" : '';

        return $this;
    }

    public function columns($columns)
    {
        $this->columns = $columns;
        return $this;
    }

    private function getColumns()
    {
        if ($this->columns) {
            $result = [];
            foreach ($this->columns as $id => $name) {
                $result[] = "--column=\"{$name}\"";
            }

            return '--hide-column=1 --column="" ' . implode(' ', $result);
        }

        return '';
    }

    public function items($items)
    {
        $this->items = $items;
        return $this;
    }

    private function getItems()
    {
        if ($this->items) {
            $result = [];
            foreach ($this->items as $i => $item) {
                $result[] = "\"{$i}\"";
                foreach ($this->columns as $id => $column) {
                    $result[] = "\"{$item[$id]}\"";
                }
            }

            return implode(' ', $result);
        }

        return '';
    }

    public function get()
    {
        $zenity  = 'LD_LIBRARY_PATH="" zenity';
        $columns = $this->getColumns();
        $items   = $this->getItems();

        $cmd = "{$zenity} {$this->type} {$this->title} {$this->text} {$this->width} {$this->height} {$columns} {$items}";

        $returnVar = null;
        $output    = null;

        $result = trim(exec($cmd, $returnVar, $output));

        if ('--question' === $this->type) {
            return (bool)$returnVar;
        }

        if ($result === '') {
            return null;
        }

        if ($result === '') {
            return null;
        }

        if ('--list' === $this->type && $this->items) {
            return $this->items[(int)$result];
        }

        return $result;
    }
}
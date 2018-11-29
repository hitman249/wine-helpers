<?php

class Logs {

    private $table  = false;
    private $length = 80;

    public function log($text='', $symbols = [], $lenght = 0, $return = false)
    {
        if (!$symbols && $this->table === true) {
            $symbols = 'line';
            $lenght  = $this->length;
            $items   = explode("\n", $text);

            if (count($items) > 1) {
                foreach ($items as $item) {
                    $this->log($item);
                }
                return;
            }
        }

        if ($symbols === 'head') {
            $symbols = ['start' => '╔', 'space' => '═', 'end' => '╗'];
        } elseif ($symbols === 'line') {
            $symbols = ['start' => '║ ', 'space' => ' ', 'end' => ' ║'];
        } elseif ($symbols === 'footer') {
            $symbols = ['start' => '╚', 'space' => '═', 'end' => '╝'];
        } elseif ($symbols === 'hr') {
            $symbols = ['start' => '╟', 'space' => '─', 'end' => '╢'];
        }

        $symbols = [
            'start' => isset($symbols['start']) ? $symbols['start'] : '',
            'space' => isset($symbols['space']) ? $symbols['space'] : ' ',
            'end'   => isset($symbols['end'])   ? $symbols['end']   : '',
        ];

        if ($lenght > 0) {
            $text    = "{$symbols['start']}{$text}";
            $len     = mb_strlen($text);
            $compare = $lenght - $len;

            if ($compare > 0) {

                $len2 = $compare - (1 + mb_strlen($symbols['end']));

                if ($len2 > 0) {
                    $end = [];
                    foreach (range(1, $len2) as $i) {
                        $end[] = $symbols['space'];
                    }
                    $end[] = $symbols['end'];
                    $end   = implode('', $end);
                    $text = "{$text}{$end}";
                }
            } else {
                $text = "{$text}{$symbols['space']}{$symbols['end']}";
            }
        }

        if ($return) {
            return $text;
        }

        print "{$text}\n";
    }

    public function logStart()
    {
        if ($this->table === false) {
            $this->log('', 'head', $this->length);
        }

        $this->table = true;
    }

    public function logStop()
    {
        if ($this->table === true) {
            $this->log('', 'footer', $this->length);
        }

        $this->table = false;
    }
}
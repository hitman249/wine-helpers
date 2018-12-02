<?php

if (!function_exists('app')) {
    /**
     * @param null|Start $type
     * @return ControllerGUI|Start
     */
    function app($type = null) {
        static $gui;
        static $start;

        if (null === $gui) {
            $gui = new ControllerGUI();
        }

        if (null === $start && $type && $type instanceof Start) {
            $start = $type;
        }

        if ($type === 'start') {
            return $start;
        }

        return $gui;
    }
}

if (!function_exists('debug_string_backtrace')) {
    function debug_string_backtrace() {

        /** @var Config $config */
        $config = app('start')->getConfig();

        $file = $config->getLogsDir() . '/debug.log';

        ob_start();

        static $time;

        if (null === $time) {
            if (file_exists($file)) {
                @unlink($file);
            }
            $time = microtime(true);
            print "Time: 0\n\n";
        } else {
            print 'Time: ' . (microtime(true) - $time) . "\n\n";
        }

        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $trace = ob_get_contents();
        ob_end_clean();

        // Remove first item from backtrace as it's this function which
        // is redundant.
        $trace = preg_replace ('/^#0\s+' . __FUNCTION__ . "[^\n]*\n/", '', $trace, 1);

        // Renumber backtrace items.
        $trace = preg_replace ('/^#(\d+)/me', '\'#\' . ($1 - 1)', $trace);

        $trace = implode("\n", array_map(function ($n) {list($a) = explode('called at', $n); return trim($a);}, explode("\n", $trace)));

        file_put_contents($file, "{$trace}\n\n", FILE_APPEND);

        return "{$trace}\n\n";
    }
}
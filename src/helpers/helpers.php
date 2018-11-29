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
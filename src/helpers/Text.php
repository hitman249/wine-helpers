<?php

class Text
{
    public static function isUtf16($text) {
        preg_match_all('/\x00/', $text, $count);

        if (count($count[0]) / strlen($text) > 0.4) {
            return true;
        }

        return false;
    }

    public static function normalize($text) {
        if (self::isUtf16($text)) {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-16');
        } elseif (md5(@iconv('Windows-1251', 'Windows-1251', $text)) !== md5(@iconv('UTF-8', 'UTF-8', $text))) {
            $text = mb_convert_encoding($text, 'UTF-8', 'Windows-1251');
        }

        return $text;
    }

    /**
     * @param string       $haystack
     * @param array|string $needle
     *
     * @return bool
     */
    public static function startsWith($haystack, $needle)
    {
        if (is_array($needle)) {
            foreach ($needle as $str) {
                if (strpos($haystack, $str) === 0) {
                    return true;
                }
            }
            return false;
        }

        return (string)$needle === "" || strpos($haystack, (string)$needle) === 0;
    }

    /**
     * @param string       $haystack
     * @param array|string $needle
     *
     * @return bool
     */
    public static function endsWith($haystack, $needle)
    {
        if (is_array($needle)) {
            foreach ($needle as $str) {
                if (substr($haystack, -strlen($str)) === $str) {
                    return true;
                }
            }
            return false;
        }

        return (string)$needle === "" || substr($haystack, (string)-strlen($needle)) === $needle;
    }

    public static function quoteArgs($args)
    {
        return implode(' ', array_map(function ($a) {return "\"{$a}\"";}, (array)$args));
    }
}
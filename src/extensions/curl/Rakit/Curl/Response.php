<?php

namespace Rakit\Curl {
    class Response {

        protected $info;

        protected $body;

        protected $error;

        protected $error_message;

        protected $header_string;

        protected $parsed_headers = array();

        protected $cookie = array();

        public function __construct(array $info, $response, $errno, $error_message)
        {
            $this->errno = $errno;
            $this->error_message = $error_message;
            $this->body = substr($response, $info['header_size']);
            $this->header_string = substr($response, 0, $info['header_size']);
            $this->info = $info;

            $this->parsed_headers = $this->parseHeaderString($this->header_string);
        }

        public function isNotFound()
        {
            return $this->getStatus() == 404;
        }

        public function isRedirect()
        {
            $status = $this->getStatus();
            if(substr($status, 0, 1) == '3') return TRUE;
        }

        public function error()
        {
            return $this->errno != 0;
        }

        public function getErrno()
        {
            return $this->errno;
        }

        public function getError()
        {
            return $this->getErrno();
        }

        public function getErrorMessage()
        {
            return $this->error_message;
        }

        public function getHeader($key, $default = null)
        {
            $key = strtolower($key);
            return isset($this->parsed_headers[$key]) ? $this->parsed_headers[$key] : $default;
        }

        public function getHeaders()
        {
            return $this->parsed_headers;
        }

        public function getCookie()
        {
            return $this->cookie;
        }

        public function length()
        {
            return strlen($this->getbody()) + strlen($this->getHeaderString());
        }

        public function getStatus()
        {
            return $this->getInfo('http_code', 0);
        }

        public function getContentType()
        {
            return $this->getInfo('content_type', FALSE);
        }

        public function isHtml()
        {
            return $this->getContentType() === 'text/html';
        }

        public function getInfo($key, $default = null)
        {
            return isset($this->info[$key])? $this->info[$key] : $default;
        }

        public function getAllInfo()
        {
            return $this->info;
        }

        public function getBody()
        {
            return $this->body;
        }

        public function getHeaderString()
        {
            return $this->header_string;
        }

        public function toArray()
        {
            $data = array(
                'headers' => $this->getHeaders(),
                'cookie' => $this->getCookie(),
                'body' => $this->getBody()
            );

            return array_merge($this->info, $data);
        }

        public function __toString()
        {
            return (string) $this->getBody();
        }

        protected function parseHeaderString($header_string)
        {
            $exp = explode("\n", $header_string);

            $headers = array();

            foreach($exp as $header) {
                $header = trim($header);

                if(preg_match('/^HTTP\/(?<v>[^ ]+)/', $header, $match)) {
                    $headers['http_version'] = $match['v'];
                    $this->info['http_version'] = $match['v'];
                } elseif('' == $header) {
                    continue;
                } else {
                    list($key, $value) = explode(':', $header, 2);
                    $key = strtolower($key);
                    $headers[$key] = trim($value);

                    if($key === 'set-cookie') {
                        $this->parseCookie($value);
                    }
                }
            }

            return $headers;
        }

        protected function parseCookie($cookie_string)
        {
            $exp = explode(';', $cookie_string);

            $cookie['value'] = array_shift($exp);

            foreach($exp as $i => $data) {
                $_parse = explode('=', $data, 2);
                $key = $_parse[0];
                $value = isset($_parse[1])? $_parse[1] : "";

                $cookie[trim(strtolower($key))] = trim($value);
            }

            $this->cookie = $cookie;
        }

    }
}

<?php

namespace Rakit\Curl {
    class Curl {

        /**
         * @var curl session $curl
         */
        protected $curl;

        /**
         * @var string $url for url target
         */
        protected $url;

        /**
         * @var array $params for store query params or post fields
         */
        protected $params = array();

        /**
         * @var array $cookies for store cookie data
         */
        protected $cookies = array();

        /**
         * @var array $options for store curl options
         */
        protected $options = array();

        /**
         * @var array $files for store file post fields
         */
        protected $files = array();

        /**
         * @var array $headers for store header settings
         */
        protected $headers = array();

        /**
         * @var int $limit_redirect_count
         */
        protected $limit_redirect_count = 0;

        /**
         * @var array $redirect_urls
         */
        protected $redirect_urls = array();

        /**
         * @var string $cookie_jar for storing session cookies while request
         */
        protected $cookie_jar = null;

        /**
         * @var bool $closed
         */
        protected $closed = FALSE;

        /**
         * @var mixed $error_message
         */
        protected $error_message = null;

        /**
         * @var mixed $errno
         */
        protected $errno = null;

        /**
         * @var Response $response for store response object after curl_exec()
         */
        public $response = null;

        /**
         * Curl constructor.
         * @param $url
         * @param array $params
         * @throws \ErrorException
         */
        public function __construct($url, array $params = array())
        {
            // check curl extension
            if ( ! extension_loaded('curl')) {
                throw new \ErrorException('cURL library need PHP cURL extension');
            }

            $this->curl = curl_init();
            $this->url = $url;
            $this->params = $params;

            $this->timeout(10);
            $this->option(CURLOPT_RETURNTRANSFER, 1);
            $this->option(CURLOPT_HEADER, TRUE);
        }

        /**
         * set timeout
         *
         * @param int $time request time limit
         * @return self
         */
        public function timeout($time)
        {
            $this->option(CURLOPT_CONNECTTIMEOUT, $time);

            return $this;
        }

        /**
         * set http authentication
         *
         * @param string username
         * @param string password
         * @param string type curl authentication type
         *
         * @return self
         */
        public function auth($username, $password, $type = 'basic')
        {
            $auth_type = constant('CURLAUTH_' . strtoupper($type));

            $this->option(CURLOPT_HTTPAUTH, $auth_type);
            $this->option(CURLOPT_USERPWD, $username . ':' . $password);

            return $this;
        }

        /**
         * set useragent
         *
         * @param string $user_agent
         * @return self
         */
        public function useragent($user_agent)
        {
            $this->option(CURLOPT_USERAGENT, $user_agent);

            return $this;
        }

        /**
         * set referer
         *
         * @param string $referer
         * @return self
         */
        public function referer($referer)
        {
            $this->option(CURLOPT_REFERER, $referer);

            return $this;
        }

        /**
         * set an option
         *
         * @param string $option option key
         * @param mixed $value option value
         * @return self
         */
        public function option($option, $value)
        {
            $this->options[$option] = $value;

            return $this;
        }

        /**
         * check if option has been setted
         *
         * @param string $option option name
         * @return bool
         */
        public function hasOption($option)
        {
            return array_key_exists($option, $this->options);
        }

        /**
         * set proxy
         *
         * @param string $url proxy url
         * @param int $port proxy port
         * @return self
         */
        public function proxy($url, $port = 80)
        {
            $this->option(CURLOPT_HTTPPROXYTUNNEL, true);
            $this->option(CURLOPT_PROXY, $url . ':' . $port);
            return $this;
        }

        /**
         * set request header
         *
         * @param string $key header key
         * @param string $value header value
         * @return self
         */
        public function header($key, $value = null)
        {
            $this->headers[] = $value === null ? $key : $key.': '.$value;

            return $this;
        }

        /**
         * getting curl session
         */
        public function getCurl()
        {
            return $this->curl;
        }

        /**
         * set request data(params)
         *
         * @param string $key
         * @param string $value
         * @return self
         */
        public function param($key, $value)
        {
            $this->params[$key] = $value;
            return $this;
        }

        /**
         * set request cookie
         *
         * @param string $key
         * @param string $value
         * @return self
         */
        public function cookie($key, $value)
        {
            $this->cookies[$key] = $value;

            return $this;
        }

        /**
         * add a file to upload
         *
         * @param string $key post file key
         * @param string $filepath
         * @param string $mimetype
         * @param string $filename posted filename
         * @return self
         */
        public function addFile($key, $filepath, $mimetype='', $filename='')
        {
            $postfield = "@$filepath;filename="
                . ($filename ?: basename($filepath))
                . ($mimetype ? ";type=$mimetype" : '');

            $this->files[$key] = $postfield;

            return $this;
        }

        /**
         * auto redirect if reseponse is redirecting (status: 3xx)
         *
         * @param int $count maximum redirecting
         */
        public function autoRedirect($count)
        {
            if(!is_int($count)) {
                throw new \InvalidArgumentException("Limit redirect must be integer");
            }

            $this->limit_redirect_count = $count;
        }

        /**
         * storing session cookie with CURLOPT_COOKIEJAR and CURLOPT_COOKIEFILE
         *
         * @param string $file file to store cookies
         */
        public function storeSession($file)
        {
            $this->option(CURLOPT_COOKIEJAR, $file);
            $this->option(CURLOPT_COOKIEFILE, $file);
            $this->cookie_jar = $file;
        }

        /**
         * set request as ajax(XMLHttpRequest)
         */
        public function ajax()
        {
            $this->header("X-Requested-With: XMLHttpRequest");
            return $this;
        }

        /**
         * execute get request
         *
         * @param array $data
         * @return \Rakit\Curl\Response
         * @throws \ErrorException
         */
        public function get(array $data = array())
        {
            $params = array_merge($this->params, $data);

            $params = !empty($params)? '?' . http_build_query($params) : '';
            $url = $this->url.$params;
            $this->option(CURLOPT_URL, $url);
            $this->option(CURLOPT_HTTPGET, TRUE);

            return $this->execute();
        }

        /**
         * execute post request
         *
         * @param array $data
         * @return \Rakit\Curl\Response
         * @throws \ErrorException
         */
        public function post(array $data = array())
        {
            $params = array_merge($this->params, $this->files, $data);

            $this->option(CURLOPT_URL, $this->url);
            $this->option(CURLOPT_POST, TRUE);
            $this->option(CURLOPT_POSTFIELDS, $params);

            return $this->execute();
        }

        /**
         * execute put request
         *
         * @param array $data
         * @return \Rakit\Curl\Response
         * @throws \ErrorException
         */
        public function put(array $data = array())
        {
            $params = array_merge($this->params, $data);

            $params = !empty($this->params)? '?' . http_build_query($this->params) : '';
            $url = $this->url.$params;
            $this->option(CURLOPT_URL, $url);
            $this->option(CURLOPT_CUSTOMREQUEST, 'PUT');

            return $this->execute();
        }

        /**
         * execute patch request
         *
         * @param array $data
         * @return \Rakit\Curl\Response
         * @throws \ErrorException
         */
        public function patch(array $data = array())
        {
            $params = array_merge($this->params, $this->files, $data);

            $this->option(CURLOPT_URL, $this->url);
            $this->option(CURLOPT_CUSTOMREQUEST, 'PATCH');
            $this->option(CURLOPT_POSTFIELDS, $params);

            return $this->execute();
        }

        /**
         * execute delete request
         *
         * @param array $data
         * @return \Rakit\Curl\Response
         * @throws \ErrorException
         */
        public function delete(array $data = array())
        {
            $params = array_merge($this->params, $data);
            $params = !empty($params)? '?' . http_build_query($params) : '';
            $url = $this->url.$params;
            $this->option(CURLOPT_URL, $url);
            $this->option(CURLOPT_CUSTOMREQUEST, 'DELETE');

            return $this->execute();
        }

        /**
         * getting redirect urls
         * @return array redirect urls
         */
        public function getRedirectUrls()
        {
            return $this->redirect_urls;
        }

        /**
         * getting last url (may be last redirected url or defined url)
         * @return string url
         */
        public function getFinalUrl()
        {
            if(count($this->redirect_urls) > 0) {
                return $this->redirect_urls[count($this->redirect_urls)-1];
            }

            return $this->url;
        }

        /**
         * execute curl
         *
         * @return \Rakit\Curl\Response
         * @throws \ErrorException
         * @throws \Exception
         */
        protected function execute()
        {
            if(TRUE === $this->closed) {
                throw new \Exception("Cannot execute curl session twice, create a new one!");
            }

            if(!empty($this->cookies)) {
                $this->option(CURLOPT_COOKIE, http_build_query($this->cookies, '', '; '));
            }

            $this->option(CURLOPT_HTTPHEADER, $this->headers);

            curl_setopt_array($this->curl, $this->options);

            $response = curl_exec($this->curl);
            $info = curl_getinfo($this->curl);
            $this->errno = $error = curl_errno($this->curl);
            $this->error_message = curl_error($this->curl);
            $this->response = new Response($info, $response, $this->errno, $this->error_message);

            $this->close();

            if($this->limit_redirect_count > 0) {
                $count_redirect = 0;
                while($this->response->isRedirect()) {
                    $this->redirect();
                    $count_redirect++;

                    if($count_redirect >= $this->limit_redirect_count) {
                        break;
                    }
                }
            }

            return $this->response;
        }

        /**
         * redirect from response 3xx
         * @throws \ErrorException
         */
        protected function redirect()
        {
            $redirect_url = $this->response->getHeader("location");
            $curl = new static($redirect_url);
            if($this->cookie_jar) {
                $curl->storeSession($this->cookie_jar);
            }
            $this->response = $curl->get();
            $this->redirect_urls[] = $redirect_url;
        }

        /**
         * getting error number after execute
         * @return int error number
         */
        public function getErrno()
        {
            return $this->errno;
        }

        /**
         * alias for getErrno
         * @return int error number
         */
        public function getError()
        {
            return $this->getErrno();
        }

        /**
         * getting error message after execute
         * @return string error message
         */
        public function getErrorMessage()
        {
            return $this->error_message;
        }

        /**
         * closing curl
         */
        protected function close()
        {
            curl_close($this->curl);
            $this->closed = TRUE;
        }

        /**
         * simple get request
         * @throws \ErrorException
         */
        public static function doGet($url, array $data = array())
        {
            return static::make($url, $data)->get();
        }

        /**
         * simple post request
         * @throws \ErrorException
         */
        public static function doPost($url, array $data = array())
        {
            return static::make($url, $data)->post();
        }

        /**
         * simple put request
         * @throws \ErrorException
         */
        public static function doPut($url, array $data = array())
        {
            return static::make($url, $data)->put();
        }

        /**
         * simple patch request
         * @throws \ErrorException
         */
        public static function doPatch($url, array $data = array())
        {
            return static::make($url, $data)->patch();
        }

        /**
         * simple delete request
         * @throws \ErrorException
         */
        public static function doDelete($url, array $data = array())
        {
            return static::make($url, $data)->delete();
        }

        /**
         * make a curl request object
         * @throws \ErrorException
         */
        public static function make($url, array $data = array())
        {
            return new static($url, $data);
        }

    }
}

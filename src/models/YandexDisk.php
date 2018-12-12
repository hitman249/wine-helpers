<?php

class YandexDisk
{
    private $url;
    private $data;
    private $cookie;
    private $headers;
    private $userAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/71.0.3578.80 Chrome/71.0.3578.80 Safari/537.36';

    /**
     * YandexDisk constructor.
     * @param string $url
     */
    public function __construct($url)
    {
        $this->url = $url;

        try {
            $request  = new \Rakit\Curl\Curl($this->url);
            $request->header('User-Agent', $this->userAgent);
            $response = $request->get();
        } catch (ErrorException $e) {
            return;
        }

        if ($request && !$response->error()) {

            $html = $response->getBody();

            $this->cookie  = $response->getCookie();
            $this->headers = $response->getHeaders();

            preg_match_all('/(\<script type="application\/json".+?>)(.+?)(<\/script>)/m', $html, $matches, PREG_SET_ORDER, 0);

            if ($matches[0][2]) {
                $json       = json_decode($matches[0][2], true);
                $this->data = $json;
            }
        }
    }

    public function getList()
    {
        $itemList = [];
        $data = $this->getData();

        foreach ($data ?: [] as $id => $resource) {
            foreach ($resource['children'] as $childId) {
                $dir  = $data[$childId]['type'] === 'dir' ? '/' : '';
                $path = "{$resource['name']}/{$data[$childId]['name']}{$dir}";

                if (endsWith($path, ['/', '.xz'])) {
                    $itemList[$childId] = $path;
                }
            }
        }

        return array_splice($itemList, 1);
    }

    public function getFileData($id)
    {
        $result = [];
        $data   = $this->getData();

        if ($data && $data[$id]) {
            return $data[$id];
        }

        return $result;
    }

    public function getData()
    {
        return $this->data['resources'];
    }

    public function getRequest()
    {
        return $this->data;
    }

    public function getPostData($id)
    {
        $file = $this->getFileData($id);
        return ['hash' => $file['path'], 'sk' => $this->getEnv('sk')];
    }

    public function getEnv($field = null)
    {
        return $field ? $this->data['environment'][$field] : $this->data['environment'];
    }

    public function getFileLink($id)
    {
        try {
            $request  = new \Rakit\Curl\Curl('https://yadi.sk/public/api/download-url');

            $request->header('Content-Type', 'text/plain');
            $request->header('Host', 'yadi.sk');
            $request->header('Origin', 'https://yadi.sk');
            $request->header('User-Agent', $this->userAgent);

            $request->cookie('yandexuid', $this->getEnv('yandexuid'));
            $request->cookie('lang', $this->getEnv('currentLang'));
            $request->cookie('tld', $this->getEnv('currentLang'));

            $raw = json_encode($this->getPostData($id));

            $response = $request->postRaw($raw);
        } catch (ErrorException $e) {
            return '';
        }

        if ($request && !$response->error()) {

            $json = $response->getBody();

            if ($json) {
                $json = json_decode($json, true);

                if ($json && !$json['error']) {
                    return $json['data']['url'];
                }
            }
        }

        return '';
    }

    public function download($id, $pathFile)
    {
        try {
            ini_set('memory_limit', '-1');
            $request = new \Rakit\Curl\Curl($this->getFileLink($id));
            $request->autoRedirect(5);
            $response = $request->get();
            file_put_contents($pathFile, $response->getBody());
        } catch (ErrorException $e) {}
    }

    public function getFolder($id)
    {
        $file = $this->getFileData($id);

        if ($file['type'] === 'dir') {
            return new self($file['meta']['short_url']);
        }

        return null;
    }
}
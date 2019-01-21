<?php

class YandexDisk
{
    private $url;
    private $data;
    private $cookie;
    private $headers;
    private $userAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/71.0.3578.80 Chrome/71.0.3578.80 Safari/537.36';
    private $parent;
    private $currentPath;

    /**
     * YandexDisk constructor.
     * @param string $url
     */
    public function __construct($url, $parent = null)
    {
        $this->url = $url;
        $this->parent = $parent;

        try {
            $request  = new \Rakit\Curl\Curl($this->url);
            $request->header('User-Agent', $this->userAgent);
            $response = $request->get();
        } catch (ErrorException $e) {
            try {
                sleep(1);
                $response = $request->get();
            } catch (ErrorException $e) {
                try {
                    sleep(3);
                    $response = $request->get();
                } catch (ErrorException $e) {
                    return;
                }
            }
        }

        if ($request && !$response->error()) {

            $html = $response->getBody();

            $this->cookie  = $response->getCookie();
            $this->headers = $response->getHeaders();

            preg_match_all('/(\<script type="application\/json".+?>)(.+?)(<\/script>)/m', $html, $matches, PREG_SET_ORDER, 0);

            if ($matches[0][2]) {
                $json       = json_decode($matches[0][2], true);
                $this->data = $json;

                if ($this->data['resources']) {
                    $this->data['original'] = $this->data['resources'];

                    $result = array_filter($this->data['resources'], function ($item) {return 'dir' === $item['type'];});

                    uasort($this->data['resources'], function ($a, $b) {
                        if ($a['type'] === 'dir' || $b['type'] === 'dir') {
                            return 0;
                        }
                        if ($a['modified'] === $b['modified']) {
                            return 0;
                        }

                        return (int)$a['modified'] > (int)$b['modified'] ? 1 : -1;
                    });

                    foreach ($this->data['resources'] as $id => $resource) {
                        if ('dir' !== $resource['type']) {
                            $result[$id] = $resource;
                        }
                    }

                    $this->data['resources'] = $result;

                    unset($result);

                    $allKeys = array_keys($this->data['resources']);

                    foreach ($this->data['resources'] as $id => $resource) {
                        $this->data['resources'][$id]['children'] = array_diff($allKeys, array_diff($allKeys, $resource['children']));
                    }
                }
            }
        }
    }

    /**
     * @return null|YandexDisk
     */
    public function getParent()
    {
        return $this->parent;
    }

    public function getList()
    {
        $itemList = [];
        $data = $this->getData();

        foreach ($this->data['original']?:[] as $resource) {
            if (!$this->currentPath && isset($resource['path'])) {
                list($hash, $_path) = explode(':', $resource['path']);
                $this->currentPath = $resource['path'];
                break;
            }
        }

        foreach ($data ?: [] as $id => $resource) {
            foreach ($resource['children'] as $childId) {
                $dir  = $data[$childId]['type'] === 'dir' ? '/' : '';
                $path = "{$resource['name']}/{$data[$childId]['name']}{$dir}";

                if (endsWith($path, ['/', '.xz'])) {
                    $itemList[$childId] = $path;
                }
            }
        }

        return $this->getParent() ? $itemList : array_splice($itemList, 1);
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

    private function getPostData($id)
    {
        $file = $this->getFileData($id);
        return ['hash' => $file['path'], 'sk' => $this->getEnv('sk')];
    }

    private function getEnv($field = null)
    {
        return $field ? $this->data['environment'][$field] : $this->data['environment'];
    }

    private function getFileLink($id)
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

    public function download($id, $path)
    {
        try {
            ini_set('memory_limit', '-1');
            $request = new \Rakit\Curl\Curl($this->getFileLink($id));
            $request->autoRedirect(5);
            $response = $request->get();
            $fileName = $this->getFileData($id);
            $pathFile = "{$path}/{$fileName['name']}";
            file_put_contents($pathFile, $response->getBody());
            unset($request, $response);

            return $pathFile;
        } catch (ErrorException $e) {}

        return '';
    }

    public function getFolder($id)
    {
        $file = $this->getFileData($id);

        if ($file['type'] === 'dir') {
            return new self($file['meta']['short_url'], $this);
        }

        return null;
    }

    public function isDir($id)
    {
        $file = $this->getFileData($id);
        return $file['type'] === 'dir';
    }

    public function getCurrentPath()
    {
        return $this->currentPath;
    }
}
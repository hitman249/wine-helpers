<?php

class Lutris
{
    private $url;
    private $data;

    /**
     * Lutris constructor.
     * @param string $url
     */
    public function __construct()
    {
        $this->url = 'https://lutris.net/api/runners?format=json&search=wine';
        $this->data = [];

        try {
            $request  = new \Rakit\Curl\Curl($this->url);
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

            $result = json_decode($response->getBody(), true);
            $result = reset($result['results']);

            foreach ($result['versions'] ?: [] as $item) {
                if (!isset($this->data[$item['architecture']])) {
                    $this->data[$item['architecture']] = [];
                }

                $this->data[$item['architecture']][] = $item['url'];
            }

            foreach ($this->data?:[] as $key => $value) {
                natsort($this->data[$key]);
                $this->data[$key] = array_reverse($this->data[$key]);
            }
        }
    }

    public function getList($type = null)
    {
        return null === $type ? array_keys($this->data) : $this->data[$type];
    }

    public function download($url, $path)
    {
        try {
            ini_set('memory_limit', '-1');
            $request = new \Rakit\Curl\Curl($url);
            $request->autoRedirect(5);
            $response = $request->get();
            $fileName = basename($url);
            $pathFile = "{$path}/{$fileName}";
            file_put_contents($pathFile, $response->getBody());
            unset($request, $response);

            return $pathFile;
        } catch (ErrorException $e) {}

        return '';
    }
}
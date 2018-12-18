<?php

class PlayOnLinux
{
    private $data;

    /**
     * PlayOnLinux constructor.
     */
    public function __construct()
    {
        $this->data = [];
    }

    public function getList($item = null)
    {
        if (null !== $item) {
            return $this->getFileList($item);
        }

        return [
            [
                'id'     => 'stable-x86',
                'name'   => 'stable-x86',
                'url'    => 'https://www.playonlinux.com/wine/binaries/linux-x86.lst',
                'prefix' => 'https://www.playonlinux.com/wine/binaries/linux-x86/',
            ],
            [
                'id'     => 'stable-x86_64',
                'name'   => 'stable-x86_64',
                'url'    => 'https://www.playonlinux.com/wine/binaries/linux-amd64.lst',
                'prefix' => 'https://www.playonlinux.com/wine/binaries/linux-amd64/',
            ],
            [
                'id'     => 'staging-x86',
                'name'   => 'staging-x86',
                'url'    => 'https://www.playonlinux.com/wine/binaries/phoenicis/staging-linux-x86/',
            ],
            [
                'id'     => 'staging-x86_64',
                'name'   => 'staging-x86_64',
                'url'    => 'https://www.playonlinux.com/wine/binaries/phoenicis/staging-linux-amd64/',
            ],
            [
                'id'     => 'upstream-x86',
                'name'   => 'upstream-x86',
                'url'    => 'https://www.playonlinux.com/wine/binaries/phoenicis/upstream-linux-x86/',
            ],
            [
                'id'     => 'upstream-x86_64',
                'name'   => 'upstream-x86_64',
                'url'    => 'https://www.playonlinux.com/wine/binaries/phoenicis/upstream-linux-amd64/',
            ],
        ];
    }

    public function getFileList($item)
    {
        if ($this->data[$item['id']]) {
            return $this->data[$item['id']];
        }

        $result = '';
        $this->data[$item['id']]  = [];

        try {
            $request  = new \Rakit\Curl\Curl($item['url']);
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
                    return $this->data[$item['id']];
                }
            }
        }

        if ($request && !$response->error()) {
            $result = $response->getBody();
        }

        if ($item['prefix']) {
            foreach (explode("\n", $result) as $line) {
                list($file, $version, $hash) = explode(';', $line);
                if (Text::endsWith($file, '.pol')) {
                    $this->data[$item['id']][] = "{$item['prefix']}{$file}";
                }
            }
        } else {
            preg_match_all('/<a href=["\'](.*?)["\']/s', $result, $matches);
            foreach ($matches[1] as $link) {
                if (Text::startsWith($link, 'PlayOnLinux') && Text::endsWith($link, ['.tar.gz', '.pol'])) {
                    $this->data[$item['id']][] = "{$item['url']}{$link}";
                }
            }
        }

        $this->data[$item['id']] = array_unique($this->data[$item['id']]);
        natsort($this->data[$item['id']]);

        $this->data[$item['id']] = array_reverse($this->data[$item['id']]);

        $this->data[$item['id']] = array_map(
            function ($item) {

                $fileName = basename($item);

                return [
                    'name' => str_replace(['PlayOnLinux-wine-', '-linux-x86.tar.gz', '-linux-amd64.tar.gz', '-linux-x86.pol', '-linux-amd64.pol'], '', $fileName),
                    'id'   => $item,
                ];
            },
            $this->data[$item['id']]
        );

        return $this->data[$item['id']];
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

            return $pathFile;
        } catch (ErrorException $e) {}

        return '';
    }
}
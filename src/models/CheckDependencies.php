<?php

class CheckDependencies {

    private $command;
    private $config;
    private $log;
    private $buffer;

    /**
     * Event constructor.
     * @param Config $config
     * @param Command $command
     */
    public function __construct(Config $config, Command $command)
    {
        $this->command = $command;
        $this->config  = $config;
    }

    public function log($text)
    {
        $logPath = $this->config->getLogsDir() . '/dependencies.log';

        if (null === $this->log) {
            $this->log = app('start')->getLog();
        }

        if (null === $this->buffer) {
            $this->buffer = app('start')->getBuffer();
            $this->buffer->clear();
            if (file_exists($logPath)) {
                @unlink($logPath);
            }
        }

        $this->log->insertLogFile($text, $logPath);
        $this->buffer->add($text);
    }

    private function formattedItem($item, $value)
    {
        $length     = 25;
        $lengthItem = mb_strlen($item);
        return  '- ' . $item . ' ' . str_repeat('.', $length - $lengthItem) . ' ' . $value;
    }

    public function check()
    {
        app()->showCheckDependencies();

        $this->log('Check dependencies.');
        $this->log('');

        $isOk = true;

        $apps = [
            'wine'       => false,
            'zenity'     => false,
            'xrandr'     => false,
            'pulseaudio' => false,
            'glxinfo'    => false,
            'grep'       => false,
            'tar'        => false,
            'wget'       => false,
            'ldconfig'   => false,
            'mksquashfs' => false,
            'ldd'        => false,
            'ps'         => false,
            'lspci'      => false,
            'fusermount' => false,
            'mount'      => false,
            'tee'        => false,
            'sed'        => false,
            'xlsfonts'   => false,
            'id'         => false,
            'cabextract' => false,
            'p7zip'      => false,
            'unrar'      => false,
            'unzip'      => false,
            'zip'        => false,
            'binutils'   => false,
            'ffmpeg'     => false,
            'sudo'       => false,
        ];

        ksort($apps);

        $percent  = 100 / (count($apps) + 4);
        $progress = 0;

        foreach ($apps as $app => $_) {
            if ($app === 'binutils') {
                $app = 'ld';
            }

            $is = trim($this->command->run("which {$app}"));

            if ($app === 'ld') {
                $app = 'binutils';
            }

            if ($is) {
                $apps[$app] = true;
                $this->log($this->formattedItem($app, 'ok'));
            } else {
                $apps[$app] = false;
                $this->log($this->formattedItem($app, 'fail'));
            }

            $progress += $percent;
            app()->getCurrentScene()->setProgress($progress);
        }

        $isVulkan = (bool)$this->command->run('ldconfig -p | grep libvulkan.so');
        $this->log($this->formattedItem('libvulkan', $isVulkan ? 'ok' : 'fail'));

        $progress += $percent;
        app()->getCurrentScene()->setProgress($progress);

        $isFuse = (bool)$this->command->run('ldconfig -p | grep libfuse.so');
        $this->log($this->formattedItem('libfuse', $isFuse ? 'ok' : 'fail'));

        $progress += $percent;
        app()->getCurrentScene()->setProgress($progress);

        $isOpenAL = (bool)$this->command->run('ldconfig -p | grep libopenal.so');
        $this->log($this->formattedItem('libopenal', $isOpenAL ? 'ok' : 'fail'));

        $progress += $percent;
        app()->getCurrentScene()->setProgress($progress);

        $isXinerama = (bool)$this->command->run('ldconfig -p | grep libXinerama.so');
        $this->log($this->formattedItem('libxinerama1', $isXinerama ? 'ok' : 'fail'));

        $progress += $percent;
        app()->getCurrentScene()->setProgress($progress);

        if (false === $apps['wine']) {
            $isOk = false;

            $this->log('');
            $this->log('Please install wine.');

            $this->log('');
            $this->log("Ubuntu:
sudo dpkg --add-architecture i386
wget -nc --no-check-certificate https://dl.winehq.org/wine-builds/Release.key
sudo apt-key add Release.key
sudo apt-add-repository https://dl.winehq.org/wine-builds/ubuntu/
sudo apt-get update
sudo apt-get install binutils cabextract p7zip-full unrar unzip wget wine zenity

Debian:
dpkg --add-architecture i386 && apt-get update
apt-get install wine32 wine binutils unzip cabextract p7zip-full unrar-free wget zenity");
        }

        if (false === $apps['zenity']) {
            $isOk = false;
            $this->log('');
            $this->log('Please install zenity.');
            $this->log("sudo apt-get install zenity");
        }

        if (false === $apps['xrandr']) {
            $isOk = false;
            $this->log('');
            $this->log('Please install xrandr.');
            $this->log("sudo apt-get install x11-xserver-utils");
        }

        if (!$isVulkan && $this->config->isDxvk()) {
            $isOk = false;
            $this->log('');
            $this->log('Please install libvulkan1.');
            $this->log("sudo apt-get install libvulkan1");
        }

        if (!$isFuse) {
            if (file_exists($this->config->getRootDir() . '/wine.squashfs')
                || file_exists($this->config->getRootDir() . '/game_info/data.squashfs')) {
                $isOk = false;
            }
            $this->log('');
            $this->log('Install libfuse.');
            $this->log("sudo apt-get install libfuse2");
        }

        if (false === $isOk) {
            $this->log('');
            $this->log("Press any key to exit.");
            ncurses_getch();
            exit(0);
        }

        $this->log('');
        $this->log("Press any key to exit.");
    }
}
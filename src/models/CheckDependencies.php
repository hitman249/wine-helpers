<?php

class CheckDependencies {

    private $command;
    private $config;
    private $log;
    private $buffer;
    private $system;

    /**
     * Event constructor.
     * @param Config $config
     * @param Command $command
     * @param System $system
     */
    public function __construct(Config $config, Command $command, System $system)
    {
        $this->command = $command;
        $this->config  = $config;
        $this->system  = $system;
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
            'xz'         => false,
            'diff'       => false,
            'patch'      => false,
            'hostname'   => false,
            'locale'     => false,
        ];

        ksort($apps);

        $percent  = 100 / (count($apps) + 4);
        $progress = 0;

        foreach ($apps as $app => $_) {
            if ($app === 'binutils') {
                $app = 'ld';
            }

            $is = trim($this->command->run("command -v {$app}"));

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

        if ($apps['ldconfig']) {
            $isVulkan = array_filter(array_map('trim', explode("\n", trim($this->command->run('ldconfig -p | grep libvulkan.so')))));

            if ($isVulkan <= 1 && $this->system->getArch() === 64) {
                $isVulkan = false;
            }

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
        } else {
            $message = 'Failed to check due to missing ldconfig';
            $this->log('');
            $this->log("libvulkan, libfuse, libopenal, libXinerama\n{$message}.");
            $progress += ($percent * 4);
            app()->getCurrentScene()->setProgress($progress);
        }

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
            if (count($this->config->findConfigsPath()) > 1) {
                $isOk = false;
            }

            $this->log('');
            $this->log('Please install zenity.');
            $this->log("sudo apt-get install zenity");
        }

        if (false === $apps['tar']) {
            $isOk = false;
            $this->log('');
            $this->log('Please install tar.');
            $this->log("sudo apt-get install tar");
        }

        if (false === $apps['xrandr']) {
            $isOk = false;
            $this->log('');
            $this->log('Please install xrandr.');
            $this->log("sudo apt-get install x11-xserver-utils");
        }

        if ($apps['ldconfig'] && !$isVulkan && $this->config->isDxvk()) {
            $isOk = false;
            $this->log('');
            $this->log('Please install libvulkan1.');
            $this->log('https://github.com/lutris/lutris/wiki/How-to:-DXVK#installing-vulkan');
        }

        if ($apps['ldconfig'] && !$isFuse) {
            if (file_exists($this->config->getRootDir() . '/wine.squashfs')
                || file_exists($this->config->getRootDir() . '/game_info/data.squashfs')) {
                $isOk = false;
            }
            $this->log('');
            $this->log('Install libfuse.');
            $this->log("sudo apt-get install libfuse2");
        }

        if ($this->config->isEsync() || $this->config->isDxvk()) {
            $currentUlimit     = $this->system->getUlimitSoft();
            $recommendedUlimit = 200000;

            if ($recommendedUlimit > $currentUlimit) {
                $this->log('');
                $this->log("Error. Current ulimit: {$currentUlimit}, Required min ulimit: {$recommendedUlimit}");
                $this->log('');
                $this->log('Add to "/etc/security/limits.conf" file and reboot system:');
                $this->log("* soft nofile {$recommendedUlimit}");
                $this->log("* hard nofile {$recommendedUlimit}");
            }
        }

        if ($this->config->isGenerationPatchesMode()) {
            if (!$apps['diff']) {
                $isOk = false;
                $this->log('');
                $this->log('Install "diff" or disable "generation_patches_mode = 0" in config.');
            }
            if (!$apps['patch']) {
                $isOk = false;
                $this->log('');
                $this->log('Install "patch" or disable "generation_patches_mode = 0" in config.');
            }
        }

        if (false === $isOk) {
            $this->log('');
            $this->log("Press any key to exit.");
            ncurses_getch();
            exit(0);
        }
    }
}
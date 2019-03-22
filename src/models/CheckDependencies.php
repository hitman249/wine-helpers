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
        $lenDot     = $length - $lengthItem;
        return  '- ' . $item . ' ' . str_repeat('.', $lenDot > 0 ?  $lenDot : 0) . ' ' . $value;
    }

    public function check()
    {
        app()->showCheckDependencies();

        $system = app('start')->getSystem();
        $update = app('start')->getUpdate();
        $driver = app('start')->getDriver()->getVersion();

        $items = [
            'Script version:   ' . $update->version(),
            'RAM:              ' . $system->getRAM() . ' Mb',
            'CPU:              ' . $system->getCPU(),
            'GPU:              ' . $system->getGPU(),
            'Distr:            ' . $system->getDistrName(),
            'Arch:             ' . $system->getArch(),
            'Linux:            ' . $system->getLinuxVersion(),
            'GPU Driver:       ' . implode(', ', array_filter($driver)),
            'Glibc:            ' . $system->getGlibcVersion(),
            'X.Org version:    ' . $system->getXorgVersion(),
            'vm.max_map_count: ' . $system->getVmMaxMapCount(),
            'ulimit soft:      ' . $system->getUlimitSoft(),
            'ulimit hard:      ' . $system->getUlimitHard(),
        ];

        $this->log('System info.');
        $this->log('');

        foreach ($items as $item) {
            $this->log($item);
        }

        $this->log('');
        $this->log('');
        $this->log('Check dependencies.');
        $this->log('');

        $isOk = true;
        $install = [];

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
            'modinfo'    => false,
            'lsmod'      => false,
            'winbindd'   => false,
            'fc-list'    => false,
        ];

        $appsPackage = [
            'wine'       => 'wine',
            'zenity'     => 'zenity',
            'xrandr'     => 'x11-xserver-utils',
            'pulseaudio' => 'pulseaudio',
            'glxinfo'    => 'mesa-utils',
            'grep'       => 'grep',
            'tar'        => 'tar',
            'wget'       => 'wget',
            'ldconfig'   => 'libc-bin',
            'mksquashfs' => 'squashfs-tools',
            'ldd'        => 'libc-bin',
            'ps'         => 'procps',
            'lspci'      => 'pciutils',
            'fusermount' => 'fuse',
            'mount'      => 'mount',
            'tee'        => 'coreutils',
            'sed'        => 'sed',
            'xlsfonts'   => 'x11-utils',
            'id'         => 'coreutils',
            'cabextract' => 'cabextract',
            'p7zip'      => 'p7zip',
            'unrar'      => 'unrar',
            'unzip'      => 'unzip',
            'zip'        => 'zip',
            'binutils'   => 'binutils',
            'ffmpeg'     => 'ffmpeg',
            'xz'         => 'xz-utils',
            'diff'       => 'diffutils',
            'patch'      => 'patch',
            'hostname'   => 'hostname',
            'locale'     => 'libc-bin',
            'modinfo'    => 'kmod',
            'lsmod'      => 'kmod',
            'winbindd'   => 'winbind',
            'fc-list'    => 'fontconfig',
        ];

        $libs = [
            'libvulkan1'        => [
                'name'   => 'libvulkan1',
                'status' => true,
                'find'   => 'libvulkan.so.1',
            ],
            'libfuse2'          => [
                'name'   => 'libfuse2',
                'status' => true,
                'find'   => 'libfuse.so.2',
            ],
            'libopenal1'        => [
                'name'   => 'libopenal',
                'status' => true,
                'find'   => 'libopenal.so.1',
            ],
            'libxinerama1'      => [
                'name'   => 'libxinerama1',
                'status' => true,
                'find'   => 'libXinerama.so.1',
            ],
            'libsdl2-2.0-0'     => [
                'name'   => 'libsdl2-2.0-0',
                'status' => true,
                'find'   => 'libSDL2-2.0.so.0',
            ],
            'libasound2'        => [
                'name'   => 'libasound2',
                'status' => true,
                'find'   => 'libasound.so.2',
            ],
            'libsm6'            => [
                'name'   => 'libsm6',
                'status' => true,
                'find'   => 'libSM.so.6',
            ],
            'libgl1'            => [
                'name'   => 'libgl1',
                'status' => true,
                'find'   => 'libGL.so.1',
            ],
            'libgif7'           => [
                'name'   => 'libgif7',
                'status' => true,
                'find'   => 'libgif.so.7',
            ],
            'libncurses5'       => [
                'name'   => 'libncurses5',
                'status' => true,
                'find'   => 'libncurses.so.5',
            ],
            'libncursesw5'      => [
                'name'   => 'libncursesw5',
                'status' => true,
                'find'   => 'libncursesw.so.5',
            ],
            'libncurses6'       => [
                'name'   => 'libncurses6',
                'status' => true,
                'find'   => 'libncurses.so.6',
            ],
            'libncursesw6'      => [
                'name'   => 'libncursesw6',
                'status' => true,
                'find'   => 'libncursesw.so.6',
            ],
            'libfreetype6'      => [
                'name'   => 'libfreetype',
                'status' => true,
                'find'   => 'libfreetype.so.6',
            ],
            'libfontconfig1'    => [
                'name'   => 'libfontconfig1',
                'status' => true,
                'find'   => 'libfontconfig.so.1',
            ],
            'libmpg123-0'       => [
                'name'   => 'libmpg123-0',
                'status' => true,
                'find'   => 'libmpg123.so.0',
            ],
            'libxcomposite1'    => [
                'name'   => 'libxcomposite1',
                'status' => true,
                'find'   => 'libXcomposite.so.1',
            ],
            'libgnutls30'       => [
                'name'   => 'libgnutls30',
                'status' => true,
                'find'   => 'libgnutls.so.30',
            ],
            'libgnutls-deb0-28' => [
                'name'   => 'libgnutls-deb0-28',
                'status' => true,
                'find'   => 'libgnutls-deb0.so.28',
            ],
            'libjpeg62'         => [
                'name'   => 'libjpeg62',
                'status' => true,
                'find'   => 'libjpeg.so.62',
            ],
            'libjpeg8'          => [
                'name'   => 'libjpeg8',
                'status' => true,
                'find'   => 'libjpeg.so.8',
            ],
            'libxslt1.1'        => [
                'name'   => 'libxslt1.1',
                'status' => true,
                'find'   => 'libxslt.so.1',
            ],
            'libxrandr2'        => [
                'name'   => 'libxrandr2',
                'status' => true,
                'find'   => 'libXrandr.so.2',
            ],
            'libpng16-16'       => [
                'name'   => 'libpng16-16',
                'status' => true,
                'find'   => 'libpng16.so.16',
            ],
            'libpng12-0'        => [
                'name'   => 'libpng12-0',
                'status' => true,
                'find'   => 'libpng12.so',
            ],
            'libtiff5'          => [
                'name'   => 'libtiff5',
                'status' => true,
                'find'   => 'libtiff.so.5',
            ],
            'libxcb1'           => [
                'name'   => 'libxcb1',
                'status' => true,
                'find'   => 'libxcb.so.1',
            ],
            'libtheora0'        => [
                'name'   => 'libtheora0',
                'status' => true,
                'find'   => 'libtheora.so.0',
            ],
            'libvorbis0a'       => [
                'name'   => 'libvorbis0a',
                'status' => true,
                'find'   => 'libvorbis.so.0',
            ],
            'zlib1g'            => [
                'name'   => 'zlib1g',
                'status' => true,
                'find'   => 'libz.so.1',
            ],
            'samba-libs'        => [
                'name'   => 'samba-libs',
                'status' => true,
                'find'   => 'libnetapi.so.0',
            ],
            'libsane1'          => [
                'name'   => 'libsane1',
                'status' => true,
                'find'   => 'libsane.so.1',
            ],
            'libcapi20-3'       => [
                'name'   => 'libcapi20-3',
                'status' => true,
                'find'   => 'libcapi20.so.3',
            ],
            'libcups2'          => [
                'name'   => 'libcups2',
                'status' => true,
                'find'   => 'libcups.so.2',
            ],
            'libgsm1'           => [
                'name'   => 'libgsm1',
                'status' => true,
                'find'   => 'libgsm.so.1',
            ],
            'libodbc1'          => [
                'name'   => 'libodbc1',
                'status' => true,
                'find'   => 'libodbc.so.2',
            ],
            'libosmesa6'        => [
                'name'   => 'libosmesa6',
                'status' => true,
                'find'   => 'libOSMesa.so.8',
            ],
            'libpcap0.8'        => [
                'name'   => 'libpcap0.8',
                'status' => true,
                'find'   => 'libpcap.so.0.8',
            ],
            'libv4l-0'          => [
                'name'   => 'libv4l-0',
                'status' => true,
                'find'   => 'libv4l1.so.0',
            ],
            'libdbus-1-3'       => [
                'name'   => 'libdbus-1-3',
                'status' => true,
                'find'   => 'libdbus-1.so.3',
            ],
            'libglib2.0-0'      => [
                'name'   => 'libglib2.0-0',
                'status' => true,
                'find'   => 'libgobject-2.0.so.0',
            ],
            'libgtk-3-0'        => [
                'name'   => 'libgtk-3-0',
                'status' => true,
                'find'   => 'libgtk-3.so.0',
            ],
            'libgstreamer1.0-0' => [
                'name'   => [
                    'gstreamer1.0-plugins-base',
                    'gstreamer1.0-plugins-good',
                    'libgstreamer1.0-0'
                ],
                'status' => true,
                'find'   => 'libgstreamer-1.0.so.0',
            ],
        ];

        $fonts = [
            'fonts-unfonts-extra' => [
                'name'   => 'fonts-unfonts-extra',
                'status' => true,
                'find'   => 'UnJamoBatang.ttf',
            ],
            'fonts-unfonts-core' => [
                'name'   => 'fonts-unfonts-core',
                'status' => true,
                'find'   => 'UnBatang.ttf',
            ],
            'fonts-wqy-microhei' => [
                'name'   => [ 'fonts-wqy-microhei', 'ttf-wqy-microhei' ],
                'status' => true,
                'find'   => 'wqy-microhei.ttc',
            ],
            'fonts-horai-umefont' => [
                'name'   => 'fonts-horai-umefont',
                'status' => true,
                'find'   => 'horai-umefont',
            ],
            'ttf-mscorefonts-installer' => [
                'name'   => 'ttf-mscorefonts-installer',
                'status' => true,
                'find'   => 'Georgia.ttf',
            ],
        ];

        ksort($apps);

        $percent  = 100 / (count($apps) + count($libs));
        $progress = 0;

        foreach ($apps as $app => $_) {
            if ($app === 'binutils') {
                $app = 'ld';
            } else if ($app === 'p7zip') {
                $app = '7z';
            }

            $is = trim($this->command->run("command -v {$app}"));

            if ($app === 'ld') {
                $app = 'binutils';
            } else if ($app === '7z') {
                $app = 'p7zip';
            }

            if ($is) {
                $apps[$app] = true;
                $this->log($this->formattedItem($app, 'ok'));
            } else {
                $apps[$app] = false;
                $this->log($this->formattedItem($app, 'fail'));

                if (!in_array($appsPackage[$app], $install, true)) {
                    $install[] = $appsPackage[$app];
                }
            }

            $progress += $percent;
            app()->getCurrentScene()->setProgress($progress);
        }

        if (trim($this->command->run('command -v fc-list'))) {
            foreach ($fonts as $key => $font) {
                $findFont = (bool)trim($this->command->run("fc-list | grep '{$font['find']}'"));

                if (!$findFont) {
                    $fonts[$key]['status'] = false;
                    foreach ((array)$font['name'] as $pkg) {
                        $install[] = $pkg;
                    }
                }

                $this->log('');
                $this->log('');
                $findLibs = implode(', ', (array)$font['name']);
                $this->log($this->formattedItem("Find font \"{$findLibs}\"", $fonts[$key]['status'] ? 'ok' : 'fail'));
            }
        }

        if ($apps['ldconfig']) {

            foreach ($libs as $key => $lib) {
                $result = [
                    'x86-64' => null,
                    'i386'   => null,
                ];
                $finds = array_filter(array_map('trim', explode("\n", trim($this->command->run("ldconfig -p | grep '{$lib['find']}'")))));

                foreach ($finds as $find) {
                    list($_fullName, $_path) = array_map('trim', explode('=>', $find));
                    list($_name, $_arch) = array_map(function ($n) {return trim($n, " \t\n\r\0\x0B()");}, explode(' (', $find));
                    $_arch = stripos($_arch,'x86-64') !== false ? 'x86-64' : 'i386';

                    if (null === $result[$_arch]) {
                        $result[$_arch] = [
                            'name' => $_name,
                            'path' => $_path,
                        ];
                    } elseif (strlen($_name) > strlen($result[$_arch]['name']) || (strlen($_name) === strlen($result[$_arch]['name']) && strlen($_path) > strlen($result[$_arch]['path']))) {
                        $result[$_arch] = [
                            'name' => $_name,
                            'path' => $_path,
                        ];
                    }
                }

                if ($this->system->getArch() === 64) {
                    $libs[$key]['status'] = (bool)($result['x86-64'] && $result['i386']);
                } else {
                    $libs[$key]['status'] = (bool)$result['i386'];
                }

                if (false === $libs[$key]['status']) {
                    foreach ((array)$lib['name'] as $findLib) {
                        if (!$result['i386']) {
                            $install[] = "{$findLib}:i386";
                        }

                        if ($this->system->getArch() === 64) {
                            if (!$result['x86-64']) {
                                $install[] = $findLib;
                            }
                        }
                    }
                }

                $this->log('');
                $this->log('');
                $findLibs = implode(', ', (array)$lib['name']);
                $this->log($this->formattedItem("Find lib \"{$findLibs}\"", $libs[$key]['status'] ? 'ok' : 'fail'));
                $this->log('');

                if ($this->system->getArch() === 64) {
                    $this->log("(x86-64) \"{$result['x86-64']['path']}\"");
                }

                $this->log("(i386)   \"{$result['i386']['path']}\"");

                $progress += $percent;
                app()->getCurrentScene()->setProgress($progress);
            }

        } else {
            $message = 'Failed to check due to missing ldconfig';
            $this->log('');
            $this->log("libvulkan, libfuse, libopenal, libXinerama, SDL2, libasound2\n{$message}.");
            $progress += ($percent * count($libs));
            app()->getCurrentScene()->setProgress($progress);
        }

        if ($install) {
            $this->log('');
            $this->log('');
            $this->log('Please install (ubuntu):');
            $this->log('sudo dpkg --add-architecture i386 && sudo apt-get update');
            $this->log('sudo apt-get install ' . implode(' ', $install));
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

        if ($apps['ldconfig'] && !$libs['libvulkan1']['status'] && $this->config->isDxvk()) {
            $isOk = false;
            $this->log('');
            $this->log('Please install libvulkan1.');
            $this->log('https://github.com/lutris/lutris/wiki/How-to:-DXVK#installing-vulkan');
        }

        if ($apps['ldconfig'] && !$libs['libfuse2']['status']) {
            if (file_exists($this->config->getRootDir() . '/wine.squashfs')
                || file_exists($this->config->getRootDir() . '/game_info/data.squashfs')) {
                $isOk = false;
            }
            $this->log('');
            $this->log('Install libfuse2.');
            $this->log("sudo apt-get install libfuse2");
        }

        if ($this->config->isDxvk()) {

            $driver = app('start')->getDriver()->getVersion();

            list($mesa) = isset($driver['mesa']) ? explode('-', $driver['mesa']) : '';

            if ($mesa) {
                $mesa = version_compare($mesa, '18.3', '<');
            }

            if ('nvidia' === $driver['vendor']) {

                $text = "\nPlease install NVIDIA driver 415.22 or newer.";

                if ('nvidia' !== $driver['driver']) {
                    $this->log($text);
                } elseif ('nvidia' === $driver['driver'] && version_compare($driver['version'], '415.22', '<')) {
                    $this->log($text);
                }

            } elseif ('amd' === $driver['vendor']) {

                $text = 'Please install AMD driver: ';

                if ('amdgpu-pro' === $driver['driver'] && version_compare($driver['version'], '18.50', '<')) {
                    $this->log("\n{$text} AMDGPU PRO 18.50 or newer.");
                } elseif ('amdgpu' === $driver['driver'] && $mesa) {
                    $this->log("\n{$text} RADV, Mesa 18.3 or newer (recommended).");
                }

            } elseif ('intel' === $driver['vendor'] && $mesa) {
                $this->log("\nPlease install Mesa 18.3 or newer.");
            }
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

        if ($this->system->getVmMaxMapCount() < 200000) {
            $this->log('');
            $this->log('Please set vm.max_map_count=262144');
            $this->log('Run as root:');
            $this->log('echo \'vm.max_map_count=262144\' >> /etc/sysctl.conf');
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
<?php

class System {

    private $command;
    private $config;

    /**
     * System constructor.
     * @param Config $config
     * @param Command $command
     */
    public function __construct(Config $config, Command $command)
    {
        $this->command = $command;
        $this->config  = $config;
    }

    public function getUserName()
    {
        static $userName = null;

        if ($userName === null) {
            $userName = trim($this->command->run('id -u -n'));
        }

        return $userName;
    }

    public function getHostname()
    {
        static $hostname = null;

        if ($hostname === null) {
            $hostname = trim($this->command->run('hostname'));
        }

        return $hostname;
    }

    public function getDesktopPath()
    {
        $isXdg = (bool)trim($this->command->run('command -v xdg-user-dir'));

        if ($isXdg) {
            return trim($this->command->run('xdg-user-dir DESKTOP'));
        }

        return '';
    }

    public function getCPU()
    {
        static $result;

        if (null === $result) {
            $cpuinfo = explode("\n", trim($this->command->run('cat /proc/cpuinfo')));

            foreach ($cpuinfo as $line) {
                if (strpos($line, 'model name') !== false) {
                    $line = explode(':', $line);
                    $result = trim(end($line));
                    return $result;
                }
            }

            $result = '';
        }

        return $result;
    }

    public function getGPU()
    {
        static $result;

        if (null === $result) {
            $gpuinfo = explode("\n", trim($this->command->run('glxinfo')));

            foreach ($gpuinfo as $line) {
                if (strpos($line, 'Device') !== false) {
                    $line = explode(':', $line);
                    $result = trim(end($line));
                    break;
                }
            }

            if (!$result) {
                $result = trim($this->command->run('lspci | grep VGA | cut -d ":" -f3'));
            }
        }

        return $result;
    }

    public function getRAM()
    {
        static $result;

        if (null === $result) {
            $meminfo = explode("\n", trim($this->command->run('cat /proc/meminfo')));

            foreach ($meminfo as $line) {
                if (strpos($line, 'MemTotal') !== false) {
                    $line = explode(':', $line);
                    $line = array_filter(explode(' ', end($line)));
                    $line = trim(reset($line));
                    $line = round($line / 1024);

                    $result = $line;

                    return $result;
                }
            }

            $result = '';
        }

        return $result;
    }

    public function getFreeRAM()
    {
        $meminfo = explode("\n", trim($this->command->run('cat /proc/meminfo')));

        foreach ($meminfo as $line) {
            if (strpos($line, 'MemAvailable') !== false) {
                $line = explode(':', $line);
                $line = array_filter(explode(' ', end($line)));
                $line = trim(reset($line));
                $line = round($line / 1024);

                return $line;
            }
        }

        return '';
    }

    public function getLinuxVersion()
    {
        static $result;

        if (null === $result) {
            $result = trim($this->command->run('uname -mrs'));
        }

        return $result;
    }

    public function getDistrName()
    {
        static $result;

        if (null === $result) {
            $release = explode("\n", trim($this->command->run('cat /etc/*-release')));

            $name = null;
            $version = null;

            foreach ($release as $line) {
                if ($name === null && strpos($line, 'DISTRIB_ID=') !== false) {
                    $line = explode('=', $line);
                    $name = trim(end($line));
                    continue;
                }

                if ($version === null && strpos($line, 'DISTRIB_RELEASE=') !== false) {
                    $line = explode('=', $line);
                    $version = trim(end($line));
                    continue;
                }
            }

            if ($name === null || $version === null) {
                foreach ($release as $line) {
                    if ($name === null && strpos($line, 'NAME=') !== false) {
                        $line = explode('=', $line);
                        $name = trim(end($line));
                        continue;
                    }

                    if ($version === null && strpos($line, 'VERSION=') !== false) {
                        $line = explode('=', $line);
                        $version = trim(end($line));
                        continue;
                    }
                }
            }

            $result = trim("{$name} {$version}");
        }

        return $result;
    }

    public function getMesaVersion()
    {
        static $result;

        if (null === $result) {
            $mesa = explode("\n", trim($this->command->run('glxinfo | grep "Mesa"')));
            $version = null;

            foreach ($mesa as $line) {
                if ($version === null && strpos($line, 'OpenGL version string') !== false) {
                    $line = explode('Mesa', $line);
                    $line = trim(end($line));
                    $line = explode(' ', $line);
                    $version = trim(reset($line));
                    break;
                }
            }

            $result = $version ?: '';
        }

        return $result;
    }

    public function getGlibcVersion()
    {
        static $result;

        if (null === $result) {

            $isGetConf = (bool)trim($this->command->run('command -v getconf'));

            if ($isGetConf) {
                $text = explode("\n", trim($this->command->run('getconf GNU_LIBC_VERSION')));

                preg_match_all('/([0-9]{1,}.[0-9]{1,})/m', trim(reset($text)), $matches, PREG_SET_ORDER, 0);

                if ($matches && $matches[0] && $matches[0][0]) {
                    $result = $matches[0][0];
                }
            }

            if (!$result) {
                $text = explode("\n", trim($this->command->run('ldd --version')));

                preg_match_all('/([0-9]{1,}.[0-9]{1,})/m', trim(reset($text)), $matches, PREG_SET_ORDER, 0);

                if ($matches && $matches[0] && $matches[0][0]) {
                    $result = $matches[0][0];
                }
            }
        }

        return $result;
    }

    public function getXrandrVersion()
    {
        static $result;

        if (null === $result) {
            $result = trim($this->command->run("xrandr --version"));
        }

        return $result;
    }

    public function getTypeGPU()
    {
        static $result;

        if (null === $result) {
            $isGlxinfo = $this->command->run("command -v glxinfo");

            if ($isGlxinfo) {
                $type = trim($this->command->run('glxinfo | grep -E "(ATI|AMD)"'));

                if ($type) {
                    $result = 'amd';
                    return $result;
                }

                $type = trim($this->command->run('glxinfo | grep "NVIDIA"'));

                if ($type) {
                    $result = 'nvidia';
                    return $result;
                }

                $type = trim($this->command->run('glxinfo | grep "Intel"'));

                if ($type) {
                    $result = 'intel';
                    return $result;
                }
            }

            return null;
        }

        return $result;
    }

    public function getUlimitHard()
    {
        return (int)trim($this->command->run('ulimit -Hn'));
    }

    public function getUlimitSoft()
    {
        return (int)trim($this->command->run('ulimit -Sn'));
    }

    public function lock()
    {
        $lock = $this->config->getRootDir() . '/run.pid';

        if (file_exists($lock)) {
            $pid = trim(file_get_contents($lock));
            if ($pid) {
                $processExists = $this->command->run("ps -p {$pid} -o comm=");
                if ($processExists) {
                    return false;
                }
            }
        }

        file_put_contents($lock, posix_getpid());

        register_shutdown_function(function () use ($lock) {
            if (file_exists($lock)) {
                @unlink($lock);
            }
        });

        return true;
    }

    public function checkPhp()
    {
        return extension_loaded('ncurses');
    }

    public function getFont()
    {
        $fonts = array_map('trim', explode("\n", trim($this->command->run('xlsfonts'))));

        $find = ['-misc-fixed-bold-r-normal--0-0-100-100-c-0-iso8859-1', '9x15bold', '10x20', 'lucidasanstypewriter-bold-18', 'lucidasans-bold-18'];

        foreach ($find as $font) {
            if (in_array($font, $fonts, true)) {
                return $font;
            }
        }

        foreach ($fonts as $font) {
            $font = trim($font);

            if (strpos($font, ' ') === false
                && strpos($font,'&') === false
                && strpos($font,'.') === false
                && strpos($font, 'bold') !== false
                && (
                    strpos($font, '100') !== false
                    || strpos($font, '110') !== false
                    || strpos($font, '120') !== false
                    || strpos($font, '130') !== false
                    || strpos($font, '140') !== false
                )
            ) {
                return $font;
            }
        }

        return '';
    }

    public function isCyrillic()
    {
        static $result;

        if (null === $result) {
            $result = (bool)trim($this->command->run('locale | grep LANG=ru'));
        }

        return $result;
    }

    public function isTar()
    {
        static $result;

        if (null === $result) {
            $result = (bool)trim($this->command->run("command -v tar"));
        }

        return $result;
    }

    public function isXz()
    {
        static $result;

        if (null === $result) {
            $result = (bool)trim($this->command->run("command -v xz"));
        }

        return $this->isTar() && $result;
    }

    public function getArch()
    {
        static $arch;

        if (null === $arch) {
            if ((bool)trim($this->command->run('command -v arch'))) {
                if (trim($this->command->run('arch')) === 'x86_64') {
                    $arch = 64;
                } else {
                    $arch = 32;
                }
            } elseif ((bool)trim($this->command->run('command -v getconf'))) {
                if (trim($this->command->run('getconf LONG_BIT')) === '64') {
                    $arch = 64;
                } else {
                    $arch = 32;
                }
            }
        }

        return $arch;
    }

    public function getXorgVersion()
    {
        static $xorg;

        if (null === $xorg) {
            if ((bool)trim($this->command->run('command -v xdpyinfo'))) {
                $result = trim($this->command->run('xdpyinfo | grep -i "X.Org version"'));
                $result = array_map('trim', explode(':', $result));
                $result = end($result);

                if ($result) {
                    $xorg = $result;
                }
            }

            if (null === $xorg) {
                $path = '/var/log/Xorg.0.log';

                if (file_exists($path)) {
                    $result = trim($this->command->run("cat {$path} | grep \"X.Org X Server\""));
                    $result = array_map('trim', explode(' ', $result));
                    $result = end($result);

                    if ($result) {
                        $xorg = $result;
                    }
                }
            }
        }

        return $xorg;
    }

    public function getVmMaxMapCount()
    {
        static $vmMaxMapCount;

        if (null === $vmMaxMapCount) {
            if ((bool)trim($this->command->run('command -v sysctl'))) {
                list($key, $value) = array_map('trim', explode('=', trim($this->command->run('sysctl vm.max_map_count'))));
                $vmMaxMapCount = (int)$value;
            }
        }

        return $vmMaxMapCount;
    }

    public function getCpuFreq()
    {
        $cpu = trim($this->command->run('cat /proc/cpuinfo'));
        $result = [];

        $cpuId = null;
        $name  = null;

        foreach (explode("\n", $cpu) as $line) {
            if (stripos($line, 'processor') !== false) {
                $exLine = explode(':', $line);
                if (trim($exLine[0]) === 'processor') {
                    $cpuId = (int)trim(end($exLine));
                }
            }

            if (stripos($line, 'model name') !== false) {
                $exLine = explode(':', $line);
                if (trim($exLine[0]) === 'model name') {
                    $name = trim(end($exLine = explode(':', $line)));
                }
            }

            if (stripos($line, 'cpu MHz') !== false) {
                $exLine = explode(':', $line);
                if (trim($exLine[0]) === 'cpu MHz') {
                    $result[] = [
                        'id'   => $cpuId,
                        'name' => $name,
                        'freq' => trim(end($exLine = explode(':', $line))),
                        'mode' => file_exists("/sys/devices/system/cpu/cpu{$cpuId}/cpufreq/scaling_governor") ?
                            trim($this->command->run("cat /sys/devices/system/cpu/cpu{$cpuId}/cpufreq/scaling_governor")) : '',
                    ];
                }
            }
        }

        return $result;
    }
}
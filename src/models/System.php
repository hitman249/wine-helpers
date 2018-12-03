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

    public function getDesktopPath()
    {
        $isXdg = (bool)trim($this->command->run('which xdg-user-dir'));

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
            $text = explode("\n", trim($this->command->run('ldd --version')));
            $text = explode(' ', trim(reset($text)));

            $result = end($text);
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
            $isGlxinfo = $this->command->run("which glxinfo");

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
}
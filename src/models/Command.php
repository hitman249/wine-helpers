<?php

class Command {

    private $config;
    private $prefix;

    /**
     * Command constructor.
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->prefix = new WinePrefix($this->config, $this);
    }


    /**
     * @param string $cmd
     * @param string|null $saveLog
     * @param bool $outputConsole
     * @return bool|string
     */
    public function run($cmd, $saveLog = null, $outputConsole = false)
    {
        if (null !== $saveLog && file_exists($saveLog)) {
            @unlink($saveLog);
        }

        $cmd = $this->cast($cmd);

        if ($outputConsole) {

            system($cmd);

            return '';
        }

        $descriptorspec = array(
            0 => array('pipe', 'r'), // stdin is a pipe that the child will read from
            1 => array('pipe', 'w'), // stdout is a pipe that the child will write to
            2 => array('pipe', 'w') // stderr is a file to write to
        );

        $pipes = array();
        $process = proc_open($cmd, $descriptorspec, $pipes);

        $output = "";

        if (!is_resource($process)) return false;

        #close child's input imidiately
        fclose($pipes[0]);

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $todo = array($pipes[1], $pipes[2]);

        while (true) {
            $read = array();
            if (!feof($pipes[1])) $read[] = $pipes[1];
            if (!feof($pipes[2])) $read[] = $pipes[2];

            if (!$read) break;

            $ready = @stream_select($read, $write = NULL, $ex = NULL, 2);

            if ($ready === false) {
                break; #should never happen - something died
            }

            foreach ($read as $r) {
                $s = fread($r, 1024);
                if ($saveLog) {
                    @file_put_contents($saveLog, $s, FILE_APPEND);
                }
                $output .= $s;
            }
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        #$code = proc_close($process);

        return $output;
    }

    public function cast($cmd)
    {
        $this->prefix->createLibsDirectory();
        $dir = $this->config->getRootDir();

        $additionalWineLibs = "{$dir}/libs/i386:{$dir}/libs/x86-64";

        $exported = [
            'export WINE'             => $this->config->wine('WINE'),
            'export WINE64'           => $this->config->wine('WINE64'),
            'export WINEPREFIX'       => $this->config->wine('WINEPREFIX'),
            'export WINESERVER'       => $this->config->wine('WINESERVER'),
            'export WINEARCH'         => $this->config->wine('WINEARCH'),
            'export WINEDEBUG'        => $this->config->wine('WINEDEBUG'),
            'export WINEDLLOVERRIDES' => $this->config->wine('WINEDLLOVERRIDES'),
            'export LD_LIBRARY_PATH'  => "\$LD_LIBRARY_PATH:{$additionalWineLibs}",
            'export DXVK_CONFIG_FILE' => $this->config->getDxvkConfigFile(),
            'export PROTON_LOG'       => $this->config->getLogsDir() . '/proton.log',
        ];

        if ($locale = $this->getLocale()) {
            $exported['export LC_ALL'] = $locale;
        }

        if (!$this->config->isEsync()) {
            $exported['export PROTON_NO_ESYNC'] = 'noesync';
        }

        if ($this->config->isDxvk()) {

            $cache = $this->config->getDxvkCacheDir();
            $logs  = $this->config->getDxvkLogsDir();

            $exported['export DXVK_STATE_CACHE_PATH'] = $cache;
            $exported['export DXVK_LOG_PATH']         = $logs;

            if (strpos($exported['export WINEDLLOVERRIDES'], 'nvapi') === false) {

                $overrides   = explode(';', $exported['export WINEDLLOVERRIDES']);
                $overrides[] = 'nvapi64,nvapi=';

                $exported['export WINEDLLOVERRIDES'] = implode(';', $overrides);
            }
        }

        if ($this->config->get('export')) {
            foreach ((array)$this->config->get('export') as $key => $value) {
                $exported["export {$key}"] = $value;
            }
        }

        $prefix = [];

        foreach ($exported as $key => $value) {
            $prefix[] = "{$key}=\"{$value}\";";
        }

        $prefix = implode(' ', $prefix);

        $cmd = "{$prefix} cd \"{$dir}\" && {$cmd}";

        return $cmd;
    }

    public function squashfuse($folder)
    {
        (new Update($this->config, $this))->downloadSquashfuse();

        $squashfuse = $this->config->getRootDir() . '/squashfuse';

        return $this->run(Text::quoteArgs($squashfuse) . ' ' . Text::quoteArgs("{$folder}.squashfs") . ' ' . Text::quoteArgs($folder));
    }

    public function zipfuse($folder)
    {
        (new Update($this->config, $this))->downloadFusezip();

        $zipfuse = $this->config->getRootDir() . '/fuse-zip';

        return $this->run(Text::quoteArgs($zipfuse) . ' ' . Text::quoteArgs("{$folder}.zip") . ' ' . Text::quoteArgs($folder));
    }

    public function umount($folder)
    {
        return $this->run('fusermount -u ' . Text::quoteArgs($folder));
    }

    public function getLocale()
    {
        static $locale;

        if (null === $locale) {
            $cmdValue = getenv('LC_ALL');

            if ($cmdValue) {
                $locale = $cmdValue;
            } else {
                exec('locale', $out, $return);
                $locales = is_array($out) ? $out : explode("\n", $out);
                $counts  = [];

                foreach ($locales as $loc) {
                    list($field, $value) = explode('=', $loc);
                    if (!$loc || !$value) {
                        continue;
                    }
                    $value = trim($value, " \t\n\r\0\x0B\"'");
                    if (!isset($counts[$value])) {
                        $counts[$value] = 0;
                    } else {
                        $counts[$value] += 1;
                    }
                }

                asort($counts);
                end($counts);
                $locale = key($counts);
            }
        }

        return $locale;
    }
}
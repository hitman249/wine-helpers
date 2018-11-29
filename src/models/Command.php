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


    public function run($cmd, $outputConsole = false)
    {
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

        $additionalWineLibs = "{$dir}/wine/lib:{$dir}/wine/lib64:{$dir}/libs/i386:{$dir}/libs/x86-64";

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
        ];

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
}
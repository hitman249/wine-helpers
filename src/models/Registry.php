<?php

class Registry
{
    private $config;
    private $command;
    private $fs;
    private $wine;
    private $replaces;

    /**
     * Registry constructor.
     * @param Config $config
     * @param Command $command
     * @param FileSystem $fs
     * @param Wine $wine
     * @param Replaces $replaces
     */
    public function __construct(Config $config, Command $command, FileSystem $fs, Wine $wine, Replaces $replaces)
    {
        $this->command  = $command;
        $this->config   = $config;
        $this->fs       = $fs;
        $this->wine     = $wine;
        $this->replaces = $replaces;
    }

    /**
     * @param array $files
     * @param callable|null $callbackLog
     * @return bool
     */
    public function apply($files, $callbackLog = null)
    {
        $regs  = ['Windows Registry Editor Version 5.00', ''];

        if ($callbackLog) {
            foreach ($files as $path) {
                $callbackLog('Apply reg file "' . $this->fs->relativePath($path) . '"');
            }
        }

        $files = array_map('file_get_contents', $files);

        foreach ($files as $file) {
            $file = Text::normalize($file);
            $file = $this->replaces->replaceByString(trim($file));
            $file = explode("\n", $file);
            if (in_array(trim(reset($file)), ['Windows Registry Editor Version 5.00', 'REGEDIT4'], true)) {
                unset($file[0]);
            }
            foreach ($file as $line) {
                if ($line !== null) {
                    $regs[] = $line;
                }
            }
        }

        if (count($regs) > 2) {
            file_put_contents($this->config->getPrefixDriveC() . '/tmp.reg', iconv('UTF-8', 'UTF-16', implode("\n", $regs)));
            $this->wine->reg([$this->config->getPrefixDriveC() . '/tmp.reg']);
            unset($regs);

            return true;
        }

        return false;
    }
}
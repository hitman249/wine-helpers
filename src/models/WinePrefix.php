<?php

class WinePrefix {

    private $command;
    private $config;
    private $wine;
    private $monitor;
    private $system;
    private $fs;
    private $update;
    private $event;

    /**
     * WinePrefix constructor.
     * @param Config $config
     * @param Command $command
     */
    public function __construct(Config $config, Command $command)
    {
        $this->command = $command;
        $this->config  = $config;
        $this->wine    = new Wine($this->config, $this->command);
        $this->monitor = new Monitor($this->config, $this->command);
        $this->system  = new System($this->config, $this->command);
        $this->fs      = new FileSystem($this->config, $this->command);
        $this->update  = new Update($this->config, $this->command);
        $this->event   = new Event($this->config, $this->command);
    }

    public function create()
    {
        if (file_exists($this->config->getRootDir() . '/wine/bin')) {
            $this->command->run('chmod +x -R ' . Text::quoteArgs($this->config->getRootDir() . '/wine/bin/'));
        }

        if (!file_exists($this->config->wine('WINEPREFIX'))) {


            $this->wine->boot();


            /**
             * Apply replace {WIDTH}, {HEIGHT}, {USER} from files
             */
            $this->updateReplaces();


            /**
             * Apply reg files
             */
            if (file_exists($this->config->getRegsDir())) {
                $regs  = ['Windows Registry Editor Version 5.00', ''];
                $files = array_map('file_get_contents', glob($this->config->getRegsDir() . '/*.reg'));

                foreach ($files as $file) {
                    $file = Text::normalize($file);
                    $file = trim($file);
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
                    file_put_contents($this->config->wine('DRIVE_C') . '/tmp.reg', implode("\n", $regs));
                    $this->wine->reg([$this->config->wine('DRIVE_C') . '/tmp.reg']);
                    unset($regs);
                }
            }


            /**
             * Copy required dlls and override them
             */
            $this->updateDlls();


            /**
             * Sandbox the prefix; Borrowed from winetricks scripts
             */
            if ($this->config->isSandbox()) {
                unlink($this->config->wine('WINEPREFIX') . '/dosdevices/z:');

                foreach (glob($this->config->wine('DRIVE_C') . '/users/' . $this->system->getUserName() . '/*') as $filePath) {
                    if (is_link($filePath)) {
                        unlink($filePath);
                        if (!mkdir($filePath, 0775, true) && !is_dir($filePath)) {
                            throw new \RuntimeException(sprintf('Directory "%s" was not created', $filePath));
                        }
                    }
                }
                $this->wine->reg(['/d', 'HKEY_LOCAL_MACHINE\Software\Microsoft\Windows\CurrentVersion\Explorer\Desktop\Namespace\{9D20AAE8-0625-44B0-9CA7-71889C2254D9}']);
                file_put_contents($this->config->wine('WINEPREFIX') . '/.update-timestamp', 'disable');
            }


            /**
             * Create symlinks to additional folders
             */
            if (file_exists($this->config->getAdditionalDir()) && file_exists($this->config->getAdditionalDir() . '/path.txt')) {

                $folders = array_filter(array_map('trim', explode("\n", file_get_contents($this->config->getAdditionalDir() . '/path.txt'))));

                if ($folders) {
                    $adds = glob($this->config->getAdditionalDir() . '/dir_*/');
                    $isCyrillic = trim($this->command->run('locale | grep LANG=ru'));

                    $folderCount = count($folders);
                    if (count($adds) >= $folderCount) {
                        foreach ($adds as $i => $path) {
                            if ($i >= $folderCount) {
                                break;
                            }

                            $add = str_replace('--REPLACE_WITH_USERNAME--', $this->system->getUserName(), trim($folders[$i], " \t\n\r\0\x0B/"));

                            if (!$isCyrillic) {
                                $add = str_replace('Мои документы', 'My Documents', $add);
                            }

                            $gameInfoAddDir = Text::quoteArgs($this->fs->relativePath($path));
                            $dirAdd = Text::quoteArgs($this->config->wine('DRIVE_C') . "/{$add}");
                            $this->command->run("mkdir -p {$dirAdd} && rm -r {$dirAdd} && ln -sfr {$gameInfoAddDir} {$dirAdd}");
                        }
                    }
                }
            }

            /**
             * Enable or disable CSMT
             */
            $this->updateCsmt();


            /**
             * Set sound driver to PulseAudio; Borrowed from winetricks
             */
            $this->updatePulse();


            /**
             * Create symlink to game directory
             */
            $this->createGameDirectory();


            /**
             * Set windows version; Borrowed from winetricks
             */
            $this->updateWinVersion();


            /**
             * Install latest dxvk (d3d11.dll and dxgi.dll)
             */
            $this->update->updateDxvk();


            /**
             * Fired hooks
             */
            $this->event->createPrefix();
            $this->event->gpu();
        }

        $this->init();
    }

    public function init()
    {
        if (!$this->wine->checkWine()) {
            (new Logs())->log('There is no Wine available in your system!');
            exit(0);
        }


        /**
         * Create symlink to game directory
         */
        $this->createGameDirectory();


        /**
         * Enable or disable CSMT
         */
        $this->updateCsmt();


        /**
         * Copy required dlls and override them
         */
        $this->updateDlls();


        /**
         * Set sound driver to PulseAudio; Borrowed from winetricks
         */
        $this->updatePulse();


        /**
         * Set windows version; Borrowed from winetricks
         */
        $this->updateWinVersion();
    }

    public function updateReplaces()
    {
        if (!file_exists($this->config->wine('WINEPREFIX'))) {
            return [];
        }

        if (!$this->config->get('replaces', 'file')) {
            return [];
        }

        $userName = $this->system->getUserName();
        $result = [];
        $width  = '';
        $height = '';

        foreach ($this->monitor->resolutions() as $output => $monitor) {
            if (!$width || !$height) {
                list($w, $h) = explode('x', $monitor['resolution']);
                $width  = $w;
                $height = $h;
            }
            if ($monitor['default']) {
                list($w, $h) = explode('x', $monitor['resolution']);
                $width  = $w;
                $height = $h;
            }
        }

        foreach ((array)$this->config->get('replaces', 'file') as $file) {

            $file = trim($file, " \t\n\r\0\x0B/");

            if (file_exists($this->config->getRootDir() . "/{$file}")) {
                $data = file_get_contents($this->config->getRootDir() . "/{$file}");
                $data = str_replace(['{WIDTH}', '{HEIGHT}', '{USER}'], [$width, $height, $userName], $data);
                @file_put_contents($this->config->getRootDir() . "/{$file}", $data);
                $result[] = "Replace {WIDTH}x{HEIGHT} -> {$width}x{$height}, {USER} -> \"{$userName}\" from file \"{$file}\"";
            }
        }

        return $result;
    }

    public function updateDlls()
    {
        if (!file_exists($this->config->wine('WINEPREFIX'))) {
            return [];
        }

        /**
         * Copy required dlls and override them
         */
        $dlls     = [];
        $isDll32  = file_exists($this->config->getDllsDir()) && file_exists($this->config->wine('DRIVE_C') . "/windows/system32/");
        $isDll64  = file_exists($this->config->getDlls64Dir()) && file_exists($this->config->wine('DRIVE_C') . "/windows/syswow64/");
        $isChange = false;
        $result   = [];

        if ($isDll32) {

            $files = glob($this->config->getDllsDir(). '/*.dll');

            if ($this->config->get('dlls', 'dll')) {
                foreach ($this->config->get('dlls', 'dll') as $dll => $rule) {
                    $path = $this->config->getDllsDir() . "/{$dll}";
                    if (file_exists($path) && !in_array($path, $files, true)) {
                        $files[] = $path;
                    }
                }
            }

            foreach ($files as $filePath) {
                $fileName = basename($filePath);
                $to = $this->config->wine('DRIVE_C') . "/windows/system32/{$fileName}";

                if (file_exists($to)) {
                    if (md5_file($filePath) === md5_file($to)) {
                        continue;
                    } else {
                        unlink($to);
                    }
                }

                $isChange = true;
                $dlls[$fileName] = 'native';
                $dll32 = $this->fs->relativePath($this->config->getDllsDir());
                $this->command->run("ln -sfr \"{$dll32}/{$fileName}\" " . Text::quoteArgs($this->config->wine('DRIVE_C') . '/windows/system32'));
                $result[] = "Add system32/{$fileName}";
            }
        }

        if ($isDll64) {

            $files = glob($this->config->getDlls64Dir() . '/*.dll');

            if ($this->config->get('dlls', 'dll')) {
                foreach ($this->config->get('dlls', 'dll') as $dll => $rule) {
                    $path = $this->config->getDlls64Dir() . "/{$dll}";
                    if (file_exists($path) && !in_array($path, $files, true)) {
                        $files[] = $path;
                    }
                }
            }

            foreach ($files as $filePath) {
                $fileName = basename($filePath);
                $to = $this->config->wine('DRIVE_C') . "/windows/syswow64/{$fileName}";

                if (file_exists($to)) {
                    if (md5_file($filePath) === md5_file($to)) {
                        continue;
                    } else {
                        unlink($to);
                    }
                }

                $isChange = true;
                $dlls[$fileName] = 'native';
                $dll64 = $this->fs->relativePath($this->config->getDlls64Dir());
                $this->command->run("ln -sfr \"{$dll64}/{$fileName}\" \" " . Text::quoteArgs($this->config->wine('DRIVE_C') . '/windows/syswow64'));
                $result[] = "Add system64/{$fileName}";
            }
        }

        if ($isChange) {
            $dlls = array_filter($dlls);
            if ($dlls) {
//                $this->runExternal("\"{$this->wineConfig['WINE']}\" reg delete \"HKEY_CURRENT_USER\\Software\\Wine\\DllOverrides\" /f");

                $configDlls = $this->config->get('dlls', 'dll');
                foreach ($dlls as $dll => $typeOverride) {
                    if (!empty($configDlls) && !empty($configDlls[$dll])) {
                        if ($configDlls[$dll] === 'nooverride') {
                            $result[] = "Register skip {$dll}";
                            continue;
                        }
                        if ($configDlls[$dll] === 'register') {
                            $this->wine->regsvr32([$dll]);
                            $result[] = "Register regsvr32 {$dll}";
                            continue;
                        }

                        $typeOverride = $configDlls[$dll];
                    }

                    $this->wine->run(['reg', 'add', 'HKEY_CURRENT_USER\\Software\\Wine\\DllOverrides', '/v', $dll, '/d', $typeOverride, '/f']);
                    $result[] = "Register {$dll}";
                }

                $result[] = "Update dll overrides.";
            }
        }

        return $result;
    }

    public function updateCsmt()
    {
        if (!file_exists($this->config->wine('WINEPREFIX'))) {
            return false;
        }

        $reg = [
            "Windows Registry Editor Version 5.00\n",
            "[HKEY_CURRENT_USER\Software\Wine\Direct3D]\n",
        ];

        $file = $this->config->wine('DRIVE_C') . '/csmt.reg';

        if ($this->config->isCsmt() && !file_exists($file)) {
            $reg[] = "\"csmt\"=-\n";
            file_put_contents($file, implode("\n", $reg));
            $this->wine->reg([$file]);

            return true;
        } elseif (!$this->config->isCsmt() && file_exists($file)) {
            $reg[] = "\"csmt\"=dword:0\n";
            file_put_contents($file, implode("\n", $reg));
            $this->wine->reg([$file]);
            unlink($file);

            return false;
        }

        return $this->config->isCsmt();
    }

    public function updatePulse()
    {
        if (!file_exists($this->config->wine('WINEPREFIX'))) {
            return false;
        }

        $reg = [
            "Windows Registry Editor Version 5.00\n",
            "[HKEY_CURRENT_USER\Software\Wine\Drivers]\n",
        ];

        $isInstallPulseAudio = (bool)trim($this->command->run("which pulseaudio"));

        if ($isInstallPulseAudio === false && $this->config->isPulse()) {
            $this->config->set('script', 'pulse', 0);
        }

        $filePulsa = $this->config->wine('DRIVE_C') . '/usepulse.reg';
        $fileAlsa  = $this->config->wine('DRIVE_C') . '/usealsa.reg';

        if ($this->config->isPulse() && !file_exists($filePulsa)) {

            $reg[] = "\"Audio\"=\"pulse\"\n";
            file_put_contents($filePulsa, implode("\n", $reg));
            $this->wine->reg([$filePulsa]);

            if (file_exists($fileAlsa)) {
                unlink($fileAlsa);
            }

            return true;

        } elseif (!$this->config->isPulse() && !file_exists($fileAlsa)) {

            $reg[] = "\"Audio\"=\"alsa\"\n";
            file_put_contents($fileAlsa, implode("\n", $reg));
            $this->wine->reg([$fileAlsa]);

            if (file_exists($filePulsa)) {
                unlink($filePulsa);
            }

            return false;
        }

        return $this->config->isPulse();
    }

    public function updateWinVersion()
    {
        if (!file_exists($this->config->wine('WINEPREFIX'))) {
            return false;
        }

        $lastwin = $this->config->wine('DRIVE_C') . '/lastwin';

        if (file_exists($lastwin)) {
            $winver = trim(file_get_contents($lastwin));

            if ($winver === $this->config->getWindowsVersion()) {
                return false;
            }
        }

        $default = [];
        $defaultWinver = 'win7';

        $reg = [
            "Windows Registry Editor Version 5.00\n",
        ];

        switch ($this->config->getWindowsVersion()) {
            case 'win2k';
                $defaultWinver = 'win2k';
                $default = [
                    'HKEY_LOCAL_MACHINE\Software\Microsoft\Windows NT\CurrentVersion' => [
                        'CSDVersion'         => 'Service Pack 4',
                        'CurrentBuildNumber' => '2195',
                        'CurrentVersion'     => '5.0',
                    ],
                    'HKEY_LOCAL_MACHINE\System\CurrentControlSet\Control\Windows'     => [
                        'CSDVersion' => 'dword:00000400',
                    ],
                ];
                break;

            case 'winxp';
                $defaultWinver = 'winxp';
                $default = [
                    'HKEY_LOCAL_MACHINE\Software\Microsoft\Windows NT\CurrentVersion' => [
                        'CSDVersion'         => 'Service Pack 3',
                        'CurrentBuildNumber' => '2600',
                        'CurrentVersion'     => '5.1',
                    ],
                    'HKEY_LOCAL_MACHINE\System\CurrentControlSet\Control\Windows'     => [
                        'CSDVersion' => 'dword:00000300',
                    ],
                ];
                break;

            case 'win7':
            default:
                $this->wine->run(['reg', 'add', 'HKLM\\System\\CurrentControlSet\\Control\\ProductOptions', '/v', 'ProductType', '/d', 'WinNT', '/f']);
                $defaultWinver = 'win7';
                $default = [
                    'HKEY_LOCAL_MACHINE\Software\Microsoft\Windows NT\CurrentVersion' => [
                        'CSDVersion'         => 'Service Pack 1',
                        'CurrentBuildNumber' => '7601',
                        'CurrentVersion'     => '6.1',
                    ],
                    'HKEY_LOCAL_MACHINE\System\CurrentControlSet\Control\Windows'     => [
                        'CSDVersion' => 'dword:00000100',
                    ],
                ];
        }

        foreach ($default as $path => $values) {
            $reg[] = "\n[{$path}]\n";
            foreach ($values as $key => $value) {
                $reg[] = "\"{$key}\"=\"{$value}\"\n";
            }
        }

        file_put_contents($lastwin, $defaultWinver);
        file_put_contents($this->config->wine('DRIVE_C') . '/setwinver.reg', implode('', $reg));

        $this->wine->reg([$this->config->wine('DRIVE_C') . '/setwinver.reg']);

        return true;
    }

    public function createGameDirectory()
    {
        /**
         * Create symlink to game directory
         */
        if (!file_exists($this->config->getPrefixGameFolder()) && file_exists($this->config->wine('WINEPREFIX'))) {

            $data = $this->fs->relativePath($this->config->getDataDir());
            $game = $this->config->getPrefixGameFolder();
            $this->command->run("mkdir -p \"{$game}\" && rm -r \"{$game}\" && ln -sfr \"{$data}\" \"{$game}\"");

            $gameFolder = trim(str_replace($this->config->wine('DRIVE_C'), '', $this->config->getPrefixGameFolder()), " \t\n\r\0\x0B/");

            return $gameFolder;
        }

        return '';
    }

    public function createLibsDirectory()
    {
        $libs = $this->config->getRootDir() . '/libs';

        if (!file_exists($libs)) {
            if (!mkdir($libs, 0775, true) && !is_dir($libs)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $libs));
            }
            if (!mkdir("{$libs}/i386", 0775, true) && !is_dir("{$libs}/i386")) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', "{$libs}/i386"));
            }
            if (!mkdir("{$libs}/x86-64", 0775, true) && !is_dir("{$libs}/x86-64")) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', "{$libs}/x86-64"));
            }

            file_put_contents("{$libs}/readme.txt",'В папки i386, x86-64 можно ложить специфичные библиотеки для wine.');
        }
    }
}
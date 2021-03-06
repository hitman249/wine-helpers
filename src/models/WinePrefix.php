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
    private $replaces;
    private $network;
    private $log;
    private $buffer;
    private $created = false;

    /**
     * WinePrefix constructor.
     * @param Config $config
     * @param Command $command
     * @param Event $event
     */
    public function __construct(Config $config, Command $command, Event $event)
    {
        $this->command  = $command;
        $this->config   = $config;
        $this->event    = $event;
        $this->wine     = new Wine($this->config, $this->command);
        $this->monitor  = new Monitor($this->config, $this->command);
        $this->system   = new System($this->config, $this->command);
        $this->fs       = new FileSystem($this->config, $this->command);
        $this->update   = new Update($this->config, $this->command);
        $this->replaces = new Replaces($this->config, $this->command, $this->fs, $this->system, $this->monitor);
        $this->network  = new Network($this->config, $this->command);
    }

    public function log($text)
    {
        $logPath = $this->config->getLogsDir() . '/prefix.log';

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

    public function create()
    {
        if (file_exists($this->config->getRootDir() . '/wine/bin')) {
            $this->command->run('chmod +x -R ' . Text::quoteArgs($this->config->getRootDir() . '/wine/bin/'));
        }

        if (!file_exists($this->config->wine('WINEPREFIX'))) {

            app('gui');
            $this->created = true;

            (new CheckDependencies($this->config, $this->command, $this->system))->check();

            app()->showPrefix();

            $this->log('Create folder "' . $this->config->wine('WINEPREFIX') . '"');
            app()->getCurrentScene()->setProgress(10);

            $this->log('Initialize ' . $this->wine->version() . ' prefix.');
            $this->wine->boot();
            @file_put_contents($this->config->wine('WINEPREFIX') . '/version', $this->wine->version());
            app()->getCurrentScene()->setProgress(20);


            /**
             * Apply replace {WIDTH}, {HEIGHT}, {USER} from files
             */
            foreach ($this->updateReplaces() as $replace) {
                $this->log($replace);
            }
            app()->getCurrentScene()->setProgress(25);


            /**
             * Sandbox the prefix; Borrowed from winetricks scripts
             */
            if ($this->updateSandbox(true)) {
                $this->log('Set sandbox.');
            }
            app()->getCurrentScene()->setProgress(30);


            /**
             * Create symlink to game directory
             */
            $this->createGameDirectory();
            app()->getCurrentScene()->setProgress(33);


            /**
             * Apply reg files
             */
            $regs = array_merge(
                app('start')->getPatch()->getRegistryFiles(),
                $this->config->getRegistryFiles()
            );
            app('start')->getRegistry()->apply($regs, function ($text) {$this->log($text);});
            app()->getCurrentScene()->setProgress(35);


            /**
             * Apply patches
             */
            if (app('start')->getPatch()->apply()) {
                $this->log('Apply patches');
            }
            app()->getCurrentScene()->setProgress(37);


            /**
             * Update dumbxinputemu
             */
            if ($this->config->getBool('script', 'dumbxinputemu')) {
                app('start')->getPatch()->create(function () {
                    (new Dumbxinputemu($this->config, $this->command, $this->fs, $this->wine))->update(function ($text) {$this->log($text);});
                });
            }
            app()->getCurrentScene()->setProgress(40);


            /**
             * Update FAudio
             */
            if ($this->config->getBool('script', 'faudio')) {
                app('start')->getPatch()->create(function () {
                    (new FAudio($this->config, $this->command, $this->fs, $this->wine))->update(function ($text) {$this->log($text);});
                });
            }
            app()->getCurrentScene()->setProgress(45);


            /**
             * Apply fixes
             */
            (new Fixes($this->config, $this->command, $this->fs, $this->wine))->update(function ($text) {$this->log($text);});
            app()->getCurrentScene()->setProgress(50);


            if ($winetricksInstall = $this->config->get('script', 'winetricks_to_install')) {
                $this->log("Winetricks install \"{$winetricksInstall}\".");
                $this->wine->winetricks(array_filter(explode(' ', $winetricksInstall)));
            }

            /**
             * Copy required dlls and override them
             */
            $this->updateDlls();
            app()->getCurrentScene()->setProgress(55);


            /**
             * Enable or disable CSMT
             */
            $this->updateCsmt();
            app()->getCurrentScene()->setProgress(70);


            /**
             * Set sound driver to PulseAudio; Borrowed from winetricks
             */
            $this->updatePulse();
            app()->getCurrentScene()->setProgress(75);


            /**
             * Set windows version; Borrowed from winetricks
             */
            $this->updateWinVersion();
            app()->getCurrentScene()->setProgress(85);


            /**
             * Install latest dxvk (d3d10.dll, d3d10_1.dll, d3d11.dll, dxgi.dll)
             */
            if ($this->config->isDxvk()) {
                app('start')->getPatch()->create(function () {
                    (new DXVK($this->config, $this->command, $this->network))->update(function ($text) {$this->log($text);});
                });
            }
            app()->getCurrentScene()->setProgress(87);


            /**
             * Install latest d9vk (d3d9.dll)
             */
            if ($this->config->isD9vk()) {
                app('start')->getPatch()->create(function () {
                    (new D9VK($this->config, $this->command, $this->network, app('start')->getFileSystem(), app('start')->getWine()))
                        ->update(function ($text) {$this->log($text);});
                });
            }
            app()->getCurrentScene()->setProgress(90);


            /**
             * Fired hooks
             */
            $this->event->createPrefix();
            app()->getCurrentScene()->setProgress(95);
            $this->event->gpu();
            app()->getCurrentScene()->setProgress(100);

            $this->log('Success!');
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
         * Update configs
         */
        $this->update->updateConfig();
        (new DXVK($this->config, $this->command, $this->network))->updateDxvkConfig();


        /**
         * Create symlink to game directory
         */
        $this->createGameDirectory();


        /**
         * Enable or disable CSMT
         */
        $this->updateCsmt();


        /**
         * Update dumbxinputemu
         */
        (new Dumbxinputemu($this->config, $this->command, $this->fs, $this->wine))->update(function ($text) {$this->log($text);});


        /**
         * Update FAudio
         */
        (new FAudio($this->config, $this->command, $this->fs, $this->wine))->update(function ($text) {$this->log($text);});


        /**
         * Apply fixes
         */
        (new Fixes($this->config, $this->command, $this->fs, $this->wine))->update(function ($text) {$this->log($text);});


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

        $result   = [];
        $userName = $this->replaces->getUserName();
        $width    = $this->replaces->getWidth();
        $height   = $this->replaces->getHeight();

        foreach ((array)$this->config->get('replaces', 'file') as $file) {

            $file = trim($file, " \t\n\r\0\x0B/");

            if (file_exists($this->config->getRootDir() . "/{$file}")) {
                $this->replaces->replaceByFile($this->config->getRootDir() . "/{$file}", true);
                $result[] = "Replace {WIDTH}x{HEIGHT} -> {$width}x{$height}, {USER} -> \"{$userName}\" ... from file \"{$file}\"";
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
        $isDll32  = file_exists($this->config->getDllsDir()) && file_exists($this->config->getWineSystem32Folder());
        $isDll64  = file_exists($this->config->getDlls64Dir()) && file_exists($this->config->getWineSyswow64Folder());
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
                $this->command->run('ln -sfr ' . Text::quoteArgs("{$dll32}/{$fileName}") . ' ' . Text::quoteArgs($this->config->wine('DRIVE_C') . '/windows/system32/'));
                $result[] = "Add system32/{$fileName}";
                $this->log("Add system32/{$fileName}");
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
                $this->command->run('ln -sfr ' . Text::quoteArgs("{$dll64}/{$fileName}") . ' ' . Text::quoteArgs($this->config->wine('DRIVE_C') . '/windows/syswow64/'));
                $result[] = "Add system64/{$fileName}";
                $this->log("Add system64/{$fileName}");
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
                            $this->log("Register skip {$dll}");
                            continue;
                        }
                        if ($configDlls[$dll] === 'register') {
                            $this->wine->regsvr32([$dll]);
                            $result[] = "Register regsvr32 {$dll}";
                            $this->log("Register regsvr32 {$dll}");
                            continue;
                        }

                        $typeOverride = $configDlls[$dll];
                    }

                    $this->wine->run(['reg', 'add', 'HKEY_CURRENT_USER\\Software\\Wine\\DllOverrides', '/v', $dll, '/d', $typeOverride, '/f']);
                    $result[] = "Register {$dll}";
                    $this->log("Register {$dll}");
                }

                $result[] = "Update dll overrides.";
                $this->log("Update dll overrides.");
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
            $this->log('CSMT enable.');

            return true;
        } elseif (!$this->config->isCsmt() && file_exists($file)) {
            $reg[] = "\"csmt\"=dword:0\n";
            file_put_contents($file, implode("\n", $reg));
            $this->wine->reg([$file]);
            unlink($file);
            $this->log('CSMT disable.');

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

        $isInstallPulseAudio = (bool)trim($this->command->run("command -v pulseaudio"));

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

            $this->log('Set sound driver to PulseAudio.');

            return true;

        } elseif (!$this->config->isPulse() && !file_exists($fileAlsa)) {

            $reg[] = "\"Audio\"=\"alsa\"\n";
            file_put_contents($fileAlsa, implode("\n", $reg));
            $this->wine->reg([$fileAlsa]);

            if (file_exists($filePulsa)) {
                unlink($filePulsa);
            }

            $this->log('Set sound driver to Alsa.');

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

            case 'win10';
                $this->wine->run(['reg', 'add', 'HKLM\\System\\CurrentControlSet\\Control\\ProductOptions', '/v', 'ProductType', '/d', 'WinNT', '/f']);
                $defaultWinver = 'win10';
                $default = [
                    'HKEY_LOCAL_MACHINE\Software\Microsoft\Windows NT\CurrentVersion' => [
                        'CSDVersion'         => '',
                        'CurrentBuildNumber' => '10240',
                        'CurrentVersion'     => '10.0',
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
        $this->log("Set Windows {$defaultWinver} version.");

        return true;
    }

    public function updateSandbox($print = false)
    {
        $update = false;

        if ($this->config->isSandbox()) {

            $z = $this->config->wine('WINEPREFIX') . '/dosdevices/z:';

            if (file_exists($z)) {
                unlink($z);
            }

            foreach (glob($this->config->wine('DRIVE_C') . '/users/' . $this->system->getUserName() . '/*') as $filePath) {
                if (is_link($filePath)) {
                    $update = true;
                    unlink($filePath);
                    if (!mkdir($filePath, 0775, true) && !is_dir($filePath)) {
                        throw new \RuntimeException(sprintf('Directory "%s" was not created', $filePath));
                    }
                }
            }

            $this->wine->reg(['/d', 'HKEY_LOCAL_MACHINE\Software\Microsoft\Windows\CurrentVersion\Explorer\Desktop\Namespace\{9D20AAE8-0625-44B0-9CA7-71889C2254D9}']);

            if ($update) {
                file_put_contents($this->config->wine('WINEPREFIX') . '/.update-timestamp', 'disable');
            }
        }

        /**
         * Create symlinks to additional folders
         */
        if (file_exists($this->config->getAdditionalDir()) && file_exists($this->config->getAdditionalDir() . '/path.txt')) {

            $folders = array_filter(array_map('trim', explode("\n", file_get_contents($this->config->getAdditionalDir() . '/path.txt'))));

            if ($folders) {
                $adds = glob($this->config->getAdditionalDir() . '/dir_*/');
                $isCyrillic = $this->system->isCyrillic();

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

                        if ($print) {
                            $this->log('Create symlink ' . $gameInfoAddDir . ' > ' . Text::quoteArgs($this->fs->relativePath($this->config->wine('DRIVE_C') . "/{$add}")));
                        }
                    }
                }
            }
        }

        return $update;
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
            $this->log("Create game folder \"{$data}\" > " . Text::quoteArgs($this->fs->relativePath($this->config->getPrefixGameFolder())) . '.');

            return $gameFolder;
        }

        return '';
    }

    public function isCreated()
    {
        return $this->created;
    }

    /**
     * @param Wine $wine
     */
    public function setWine($wine)
    {
        $this->wine = $wine;
    }
}
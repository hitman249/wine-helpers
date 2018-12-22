<?php

class Snapshot
{
    private $config;
    private $command;
    private $fs;
    private $wine;
    private $replaces;
    private $system;
    private $folders;
    private $shapshotFile;
    private $driveC;
    private $shapshotDir;
    private $shapshotBeforeDir;
    private $patchDir;


    /**
     * Snapshot constructor.
     * @param Config $config
     * @param Command $command
     * @param FileSystem $fs
     * @param Wine $wine
     * @param Replaces $replaces
     * @param System $system
     */
    public function __construct(Config $config, Command $command, FileSystem $fs, Wine $wine, Replaces $replaces, System $system)
    {
        $this->command  = $command;
        $this->config   = $config;
        $this->fs       = $fs;
        $this->wine     = $wine;
        $this->replaces = $replaces;
        $this->system   = $system;

        $this->folders = [
            'Program Files',
            'ProgramData',
            'users',
            'windows',
        ];
        $this->shapshotFile      = $this->config->getCacheDir() . '/filelist.shapshot';
        $this->shapshotDir       = $this->config->getCacheDir() . '/shapshot';
        $this->shapshotBeforeDir = $this->config->getCacheDir() . '/before';
        $this->patchDir          = $this->config->getCacheDir() . '/patch';
        $this->driveC            = $this->config->wine('DRIVE_C');
    }

    private function create()
    {
        $this->fs->rm($this->shapshotFile);
        $this->fs->rm($this->shapshotDir);

        foreach ($this->folders as $folder) {
            $this->read("{$this->driveC}/{$folder}");
        }
        foreach (glob("{$this->driveC}/*") as $item) {
            if (!is_dir($item)) {
                $relativePath = $this->fs->relativePath($item, $this->driveC);
                file_put_contents($this->shapshotFile, "{$relativePath};file;;\n", FILE_APPEND);
            }
        }

        $this->fs->mkdirs([$this->shapshotDir]);
        $this->fs->mv($this->shapshotFile, "{$this->shapshotDir}/filelist.shapshot");

        $this->wine->reg(['/E', "{$this->driveC}/regedit.reg"]);
        $this->fs->mv("{$this->driveC}/regedit.reg", "{$this->shapshotDir}/regedit.reg");

        return true;
    }

    private function read($dir)
    {
        $gameFolder   = $this->fs->relativePath($this->config->getPrefixGameFolder(), $this->driveC);
        $relativePath = $this->fs->relativePath($dir, $this->driveC);

        if ($gameFolder === $relativePath) {
            return;
        }

        file_put_contents($this->shapshotFile, "{$relativePath};dir;;\n", FILE_APPEND);

        foreach (scandir($dir, SCANDIR_SORT_NONE) as $object) {
            if ($object !== '.' && $object !== '..') {
                $path = "{$dir}/{$object}";
                $relativePath = $this->fs->relativePath($path, $this->driveC);

                if (is_dir($path)) {
                    $this->read($path);
                } else {
                    $md5  = md5_file($path);
                    $size = filesize($path);
                    file_put_contents($this->shapshotFile, "{$relativePath};file;{$md5};{$size}\n", FILE_APPEND);
                }
            }
        }
    }

    public function createBefore()
    {
        if ($this->create()) {
            $this->fs->rm($this->shapshotBeforeDir);
            $this->fs->mv($this->shapshotDir, $this->shapshotBeforeDir);

            return true;
        }

        return false;
    }

    public function createAfter()
    {
        if ($this->create()) {
            $this->fs->rm($this->patchDir);
            $this->fs->mkdirs([$this->patchDir]);

            foreach (glob("{$this->shapshotBeforeDir}/*.reg") as $i => $before) {

                $fileName = basename($before);

                $after = "{$this->shapshotDir}/{$fileName}";

                if (file_exists($after)) {
                    $prefix  = $i === 0 ? '' : $i;
                    $regedit = $this->replaces->replaceToTemplateByString($this->getRegeditChanges($before, $after));
                    file_put_contents("{$this->patchDir}/changes{$prefix}.reg", $regedit);
                }
            }

            $userFolder        = 'users/' . $this->system->getUserName();
            $userFolderReplace = 'users/default';

            $changes = $this->getFilesChanges("{$this->shapshotBeforeDir}/filelist.shapshot", "{$this->shapshotDir}/filelist.shapshot");

            foreach ($changes as $file) {
                $in   = "{$this->driveC}/{$file}";
                $file = (Text::startsWith($file, $userFolder) ? str_replace($userFolder, $userFolderReplace, $file) : $file);
                $out  = "{$this->patchDir}/files/{$file}";

                $dir = dirname($out);
                $this->fs->mkdirs([$dir]);
                $this->fs->cp($in, $out);
            }

            return true;
        }

        return false;
    }

    public function getPatchDir()
    {
        return $this->patchDir;
    }

    public function getRegeditChanges($file1, $file2)
    {
        $diff        = new Diff($this->config, $this->command);
        $compare     = $diff->compareFiles($file1, $file2, 'UTF-16LE');
        $allSections = array_filter($diff->getFile2Data(), function ($line) { return Text::startsWith($line[0], '['); });
        unset($diff);
        $changes     = array_filter($compare[Diff::INSERTED], function ($line) { return !Text::startsWith($line, '['); });

        $result      = [];
        $new         = null;
        $old         = null;
        $prevChange  = null;
        $findSection = null;

        foreach ($changes as $lineNumber => $line) {
            if (!$prevChange || ($prevChange + 1) !== $lineNumber) {
                $prevChange = $lineNumber;
                $findSection = array_filter($allSections, function ($key) use ($lineNumber) {return $lineNumber > $key;}, ARRAY_FILTER_USE_KEY);
                $findSection = end($findSection);
            }

            if (!isset($result[$findSection])) {
                $result[$findSection] = [];
            }

            $result[$findSection][] = $line;
        }

        if (!$result) {
            return '';
        }

        $text = "Windows Registry Editor Version 5.00\n";

        foreach ($result as $section => $lines) {
            $text .= "\n{$section}\n" . implode("\n", $lines) . "\n";
        }

        return $text;
    }

    public function getFilesChanges($file1, $file2)
    {
        $diff    = new Diff($this->config, $this->command);
        $compare = $diff->compareFiles($file1, $file2);
        unset($diff);

        $result = [];

        foreach ($compare[Diff::INSERTED] as $lineNumber => $line) {
            list($path, $type, $size) = explode(';', $line);
            if ($type === 'file') {
                $result[] = $path;
            }
        }

        return $result;
    }
}
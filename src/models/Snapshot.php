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
                    $changes = $this->getRegeditChanges($before, $after);
                    if (false && $changes[Diff::DELETED]) {
                        file_put_contents("{$this->patchDir}/0-deleted{$prefix}.reg", $changes[Diff::DELETED]);
                    }
                    if ($changes[Diff::INSERTED]) {
                        file_put_contents("{$this->patchDir}/changes{$prefix}.reg", $changes[Diff::INSERTED]);
                    }
                }
            }

            $userFolder        = 'users/' . $this->system->getUserName();
            $userFolderReplace = 'users/default';

            $changes = $this->getFilesChanges("{$this->shapshotBeforeDir}/filelist.shapshot", "{$this->shapshotDir}/filelist.shapshot");

            foreach ($changes[Diff::INSERTED] as $file) {
                $in   = "{$this->driveC}/{$file}";
                $file = (Text::startsWith($file, $userFolder) ? str_replace($userFolder, $userFolderReplace, $file) : $file);
                $out  = "{$this->patchDir}/files/{$file}";

                $dir = dirname($out);
                $this->fs->mkdirs([$dir]);
                $this->fs->cp($in, $out);
            }

            if ($changes[Diff::INSERTED]) {
                if ($this->fs->pack("{$this->patchDir}/files")) {
                    $this->fs->rm("{$this->patchDir}/files");
                }
            }

            if (false && $changes[Diff::DELETED]) {
                $files = array_map(
                    function ($file) use ($userFolder, $userFolderReplace) {
                        return (Text::startsWith($file, $userFolder) ? str_replace($userFolder, $userFolderReplace, $file) : $file);
                    },
                    $changes[Diff::DELETED]
                );
                file_put_contents("{$this->patchDir}/deleted", implode("\n", $files));
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
        $diff    = new Diff($this->config, $this->command);
        $compare = $diff->compareFiles($file1, $file2, 'UTF-16LE');

        return [
            Diff::INSERTED => $this->getRegeditInserted($diff, $compare),
            Diff::DELETED  => $this->getRegeditDeleted($diff, $compare),
        ];
    }

    /**
     * @param Diff $diff
     * @param array $compare
     * @return string
     */
    private function getRegeditDeleted($diff, &$compare)
    {
        $sections = array_filter($diff->getFile1Data(), function ($line) { return Text::startsWith($line[0], '['); });
        $deleted  = array_filter($compare[Diff::DELETED], function ($line) { return !Text::startsWith($line, '[') && !Text::startsWith($line, '  '); });

        $result      = [];
        $new         = null;
        $old         = null;
        $prevChange  = null;
        $findSection = null;

        foreach ($deleted as $lineNumber => $line) {
            if (!$prevChange || ($prevChange + 1) !== $lineNumber) {
                $prevChange = $lineNumber;
                $findSection = array_filter($sections, function ($key) use ($lineNumber) {return $lineNumber > $key;}, ARRAY_FILTER_USE_KEY);
                $findSection = end($findSection);
            }

            if (!isset($result[$findSection])) {
                $result[$findSection] = [];
            }

            list($field) = explode('=', $line);

            if ($field) {
                $result[$findSection][] = "{$field}=-";
            }
        }

        $deletedSections = array_filter($compare[Diff::DELETED], function ($line) use (&$result) {
            return Text::startsWith($line, '[') && empty($result[$line]);
        });

        foreach ($deletedSections as $section) {
            $result[str_replace('[', '[-', $section)] = [];
        }

        if (!$result) {
            return '';
        }

        $text = "Windows Registry Editor Version 5.00\n";

        foreach ($result as $section => $lines) {
            $text .= "\n{$section}\n" . implode("\n", $lines) . "\n";
        }

        return $this->replaces->replaceToTemplateByString($text);
    }

    /**
     * @param Diff $diff
     * @param array $compare
     * @return string
     */
    private function getRegeditInserted($diff, &$compare)
    {
        $sections = array_filter($diff->getFile2Data(), function ($line) { return Text::startsWith($line[0], '['); });
        $inserted = array_filter($compare[Diff::INSERTED], function ($line) { return !Text::startsWith($line, '['); });

        $result      = [];
        $new         = null;
        $old         = null;
        $prevChange  = null;
        $findSection = null;

        foreach ($inserted as $lineNumber => $line) {
            if (!$prevChange || ($prevChange + 1) !== $lineNumber) {
                $prevChange = $lineNumber;
                $findSection = array_filter($sections, function ($key) use ($lineNumber) {return $lineNumber > $key;}, ARRAY_FILTER_USE_KEY);
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

        return $this->replaces->replaceToTemplateByString($text);
    }

    public function getFilesChanges($file1, $file2)
    {
        $diff    = new Diff($this->config, $this->command);
        $compare = $diff->compareFiles($file1, $file2);
        unset($diff);

        $inserted = [];
        foreach ($compare[Diff::INSERTED] as $line) {
            list($path, $type, $size) = explode(';', $line);
            if ($type === 'file') {
                $inserted[] = $path;
            }
        }

        $deleted = [];
        foreach ($compare[Diff::DELETED] as $line) {
            list($path, $type, $size) = explode(';', $line);
            if ($type === 'file' && !in_array($path, $inserted, true)) {
                $deleted[] = $path;
            }
        }

        return [
            Diff::INSERTED => $inserted,
            Diff::DELETED  => $deleted,
        ];
    }
}
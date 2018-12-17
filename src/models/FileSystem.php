<?php

class FileSystem {

    private $command;
    private $config;

    /**
     * FileSystem constructor.
     * @param Config $config
     * @param Command $command
     */
    public function __construct(Config $config, Command $command)
    {
        $this->command = $command;
        $this->config  = $config;
    }

    public static function getDirectorySize($path)
    {
        $bytes = 0;
        $path  = realpath($path);

        if ($path !== false && $path != '' && file_exists($path)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object) {
                $bytes += $object->getSize();
            }
        }

        return $bytes;
    }

    public function relativePath($absPath, $path = null)
    {
        return trim(str_replace($path === null ? $this->config->getRootDir() : $path, '', $absPath), " \t\n\r\0\x0B/");
    }

    public function rm($dir)
    {
        if (file_exists($dir) && !is_dir($dir)) {
            unlink($dir);
            return true;
        }

        if (is_dir($dir)) {
            $objects = scandir($dir, SCANDIR_SORT_NONE);
            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    if (is_dir("{$dir}/{$object}")) {
                        $this->rm("{$dir}/{$object}");
                    } else {
                        unlink("{$dir}/{$object}");
                    }
                }
            }
            rmdir($dir);
        }

        return true;
    }

    public function cp($in, $out)
    {
        if (file_exists($in) && !is_dir($in)) {
            copy($in, $out);
            return true;
        }

        if (!file_exists($in) || !is_dir($in)) {
            return false;
        }

        $mode = 0775;

        if (!mkdir($out, $mode, true) && !is_dir($out)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $out));
        }

        foreach (
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($in, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST) as $item
        ) {
            if ($item->isDir()) {
                if (!mkdir($concurrentDirectory = $out . DIRECTORY_SEPARATOR . $iterator->getSubPathName(), $mode) && !is_dir($concurrentDirectory)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
                }
            } else {
                copy($item, $out . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            }
        }

        return true;
    }

    public function mv($in, $out)
    {
        if (file_exists($in) && !is_dir($in)) {
            rename($in, $out);
            return true;
        }

        if (!is_dir($in) || file_exists($out)) {
            return false;
        }

        $mode = 0775;

        if (!mkdir($out, $mode, true) && !is_dir($out)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $out));
        }

        foreach (new DirectoryIterator($in) as $iterator) {
            if ($iterator->isFile() || $iterator->isLink()) {
                rename($iterator->isLink() ? $iterator->getPathName() : $iterator->getRealPath(), "{$out}/" . $iterator->getFilename());
            } else if (!$iterator->isDot() && $iterator->isDir()) {
                $this->mv($iterator->getRealPath(), "{$out}/{$iterator}");
                if (file_exists($iterator->getRealPath())) {
                    if (is_dir($iterator->getRealPath())) {
                        rmdir($in);
                    } else {
                        unlink($iterator->getRealPath());
                    }
                }
            }
        }

        if (file_exists($in)) {
            if (is_dir($in)) {
                rmdir($in);
            } else {
                unlink($in);
            }
        }

        return true;
    }

    public function mkdirs($dirs)
    {
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
                }
            }
        }
    }

    public function link($in, $out)
    {
        return $this->command->run("ln -sfr \"{$in}\" \"{$out}\"");
    }

    public function unpackXz($inFile, $outDir, $type = 'xf')
    {
        if (!app('start')->getSystem()->isXz()) {
            return false;
        }

        if (!file_exists($inFile) || is_dir($inFile)) {
            return false;
        }

        if (file_exists($outDir)) {
            $this->rm($outDir);
        }

        $dir = dirname($inFile);
        $rnd = mt_rand(10000, 99999);
        $tmpDir = "{$dir}/tmp_{$rnd}";
        $this->mkdirs([$tmpDir]);

        if (!file_exists($tmpDir)) {
            return false;
        }

        $fileName = basename($inFile);
        $mvFile   = "{$tmpDir}/{$fileName}";
        $this->mv($inFile, $mvFile);
        $this->command->run("cd \"{$tmpDir}\" && tar {$type} \"./{$fileName}\"");
        $this->rm($mvFile);

        $find = glob("{$tmpDir}/*");

        $path = $tmpDir;

        if (count($find) === 1) {
            $path = reset($find);
        }

        $this->mv($path, $outDir);

        if (file_exists($tmpDir)) {
            $this->rm($tmpDir);
        }

        return true;
    }

    public function unpackGz($inFile, $outDir)
    {
        return $this->unpackXz($inFile, $outDir, '-xzf');
    }

    public function unpack($inFile, $outDir)
    {
        if (Text::endsWith($inFile, '.tar.xz')) {
            return $this->unpackXz($inFile, $outDir);
        }
        if (Text::endsWith($inFile, '.tar.gz')) {
            return $this->unpackGz($inFile, $outDir);
        }

        return false;
    }
}
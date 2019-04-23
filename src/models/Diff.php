<?php

class Diff
{
    const UNMODIFIED = 0;
    const DELETED    = 1;
    const INSERTED   = 2;

    private $config;
    private $command;
    private $file1Path;
    private $file2Path;
    private $file1Data;
    private $file2Data;

    /**
     * Diff constructor.
     * @param Config $config
     * @param Command $command
     */
    public function __construct(Config $config, Command $command)
    {
        $this->command  = $command;
        $this->config   = $config;
    }

    public function compareFiles($file1, $file2, $encoding = 'UTF-8')
    {
        $this->file1Path = $file1;
        $this->file2Path = $file2;

        $this->file1Data = $encoding === 'UTF-8' ? file_get_contents($file1) : iconv($encoding, 'UTF-8', file_get_contents($file1));
        $this->file1Data = preg_split('/\n|\r\n?/', $this->file1Data);

        $this->file2Data = $encoding === 'UTF-8' ? file_get_contents($file2) : iconv($encoding, 'UTF-8', file_get_contents($file2));
        $this->file2Data = preg_split('/\n|\r\n?/', $this->file2Data);

        return $this->diff($this->file1Path, $this->file2Path);
    }

    public function getFile1Data()
    {
        return $this->file1Data;
    }

    public function getFile2Data()
    {
        return $this->file2Data;
    }

    private function parse($diff)
    {
        $result = [
            self::DELETED  => [],
            self::INSERTED => [],
        ];

        $from = 0;
        $to   = 0;

        $sectionType = self::DELETED;

        foreach (explode("\n", $diff) as $line) {
            if (mb_strpos($line, '*** ') !== false && mb_strpos($line, ' ****') === false) {
                continue;
            }
            if (mb_strpos($line, '--- ') !== false && mb_strpos($line, ' ----') === false) {
                continue;
            }
            if (mb_strpos($line, '***************') !== false) {
                $sectionType = self::DELETED;
                continue;
            }
            if (mb_strpos($line, '--- ') !== false && mb_strpos($line, ' ----') !== false) {
                $sectionType = self::INSERTED;
                list($from, $to) = explode(',', trim($line, "-* \t\n\r\0\x0B"));
                continue;
            }
            if (mb_strpos($line, '*** ') !== false && mb_strpos($line, ' ****') !== false) {
                $sectionType = self::DELETED;
                list($from, $to) = explode(',', trim($line, "-* \t\n\r\0\x0B"));
                continue;
            }

            if (Text::startsWith($line, ['!', '+', '-'])) {
                if (self::DELETED === $sectionType) {
                    $result[self::DELETED][(int)$from - 1] = $this->file1Data[(int)$from - 1];
                } elseif (self::INSERTED === $sectionType) {
                    $result[self::INSERTED][(int)$from - 1] = $this->file2Data[(int)$from - 1];
                }
            }

            $from = (int)$from + 1;
        }

        return $result;
    }

    private function diff($file1, $file2)
    {
        $diff = $this->command->run("diff --text -c \"{$file1}\" \"{$file2}\"");
        return $this->parse($diff);
    }

    private function patch($file, $path, $result)
    {
        return $this->command->run("patch \"{$file}\" -i \"{$path}\" -o \"{$result}\" -t");
    }
}
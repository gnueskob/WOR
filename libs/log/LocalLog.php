<?php

namespace lsb\Log;

use lsb\Libs\ILog;
use lsb\Config\Config;
use phpDocumentor\Reflection\File;

class LocalLog implements ILog
{
    private $dir;
    private $limit;
    private $msg = [];

    public function __construct(array $conf)
    {
        $this->dir = $conf['dir'];
        $this->limit = $conf['limit'];
    }

    private function getSymbolicLinkPath(string $category)
    {
        return "{$this->dir}/{$category}/current";
    }

    private function getLogFileHandle(string $category)
    {
        $categoryDir = $this->dir . '/' . $category;
        if (!file_exists($categoryDir)) {
            mkdir($categoryDir, 0777);
        }

        $symbolicFile = $this->getSymbolicLinkPath($category);
        if (file_exists($symbolicFile)) {
            return fopen($symbolicFile, 'a');
        } else {
            $fileName = "{$categoryDir}/{$category}_0";
            $handle = fopen($fileName, 'w');
            symlink($fileName, $symbolicFile);
            return $handle;
        }
    }

    private function writeToFile(string $category): void
    {
        $logFileHandle = $this->getLogFileHandle($category);
        $msgCnt = count($this->msg[$category]);
        for ($i = 0; $i < $msgCnt; $i++) {
            $logFileHandle = $this->appendLogMsgToFile($logFileHandle, $category, $this->msg[$category][$i]);
        }
        fclose($logFileHandle);
    }

    private function appendLogMsgToFile($logFileHandle, $category, $msg)
    {
        $symbolicFile = $this->getSymbolicLinkPath($category);
        $originFilePath = readlink($symbolicFile);

        // the size of file (bytes)
        $size = filesize($originFilePath);

        if ($size >= $this->limit) {
            $path = explode('_', $originFilePath);
            $prefixNewFilePath = $path[0];
            $newFileNumber = $path[1] + 1;
            $fileName = "{$prefixNewFilePath}_{$newFileNumber}";
            fclose($logFileHandle);
            $logFileHandle = fopen($fileName, 'w');

            unlink($symbolicFile);
            symlink($fileName, $symbolicFile);
        }

        fwrite($logFileHandle, $msg);

        return $logFileHandle;
    }


    public function addLog(string $category, string $msg): void
    {
        $this->msg[$category][] = $msg . PHP_EOL;
    }

    public function flushLog(): void
    {
        foreach ($this->msg as $category => $msg) {
            $this->writeToFile($category);
        }
    }

    public function writeLog(string $category, string $msg): void
    {
        $logFileHandle = $this->getLogFileHandle($category);
        $this->appendLogMsgToFile($logFileHandle, $category, $msg . PHP_EOL);
    }
}

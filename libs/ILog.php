<?php

namespace lsb\Libs;

interface ILog
{
    public function addLog(string $category, string $msg);
    public function flushLog();
    public function writeLog(string $category, string $msg);
}

<?php

namespace lsb\Libs;

class Singleton
{
    public static function getInstance()
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new static();
        }
        return $instance;
    }

    protected function __construct()
    {
    }

    private function __clone()
    {
    }
}

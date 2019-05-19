<?php

namespace lsb\Libs;

class Redis extends Singleton {

    # private $connection;

    private function __construct()
    {
        parent::__construct();
        # $this->connection = new RedisConnection;
    }

}


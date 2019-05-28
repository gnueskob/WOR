<?php

namespace lsb\Libs;

use Memcached as Mcd;
use Exception;
use lsb\Config\Config;

// 이름 변경
class Memcached extends Singleton
{
    private $mcd;

    public function getMemcached(): Mcd
    {
        return $this->mcd;
    }

    protected function __construct()
    {
        parent::__construct();

        $conf = Config::getInstance()->getConfig('memcached');
        $host = $conf['host'];
        $port = $conf['port'];

        // TODO: 연결 실패시 재 시도 로직 추가
        try {
            $mcd = new Mcd();
            $mcd->addServer($host, $port);
            $this->mcd = $mcd;
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
}

//$redis = \lsb\Libs\MemcachedInstance::getInstance()->getMemcached();
//$value = $redis->getStats();
//print("<pre>");
//print_r($value);
//print("</pre>");

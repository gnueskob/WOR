<?php


namespace lsb\Libs;

define('__JSON__', 0);
define('__TEXT__', 1);
define('__HTML__', 2);

class Response implements IResponse
{
    private $type = __JSON__;

    public static function getInstance()
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new static();
        }
        return $instance;
    }

    private function __clone()
    {
    }

    private function __construct()
    {
    }

    public function setHeader($serverProtocol, $code, string $msg)
    {
        header("{$serverProtocol} {$code} {$msg}");
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function send($res)
    {
        header("Access-Control-Allow-Origin: *");

        switch ($this->type) {
            case __JSON__:
                header("Content-Type: application/json; charset=UTF-8");
                break;
            case __TEXT__:
                header("Content-Type: text/text; charset=UTF-8");
                break;
            case __HTML__:
                header("Content-Type: text/html; charset=UTF-8");
                break;
            default:
                break;
        }

        if ($this->type === __JSON__) {
            $res = json_encode($res);
        }
        echo $res;
    }
}

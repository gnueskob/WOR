<?php


namespace lsb\Libs;

define('__JSON__', 0);
define('__TEXT__', 1);
define('__HTML__', 2);

class Response implements IResponse
{
    private $type = null;
    private $status = 200;
    private $msg = 'OK';

    public function __construct()
    {
        $this->type = __JSON__;
    }

    public function setHeader(string $protocol, int $code, string $msg): void
    {
        header("{$protocol} {$code} {$msg}");
        header("Content-Type: text/html; charset=UTF-8");
    }

    public function setType(int $type): void
    {
        $this->type = $type;
    }

    public function send($res): void
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

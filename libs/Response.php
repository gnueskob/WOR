<?php


namespace lsb\Libs;

define('__JSON__', 0);
define('__TEXT__', 1);

class Response implements IResponse
{
    private $type = __JSON__;

    public function __construct($type = __JSON__)
    {
        $this->type = $type;
        header("Access-Control-Allow-Origin: *");

        switch ($type) {
            case __JSON__:
                header("Content-Type: application/json; charset=UTF-8");
                break;
            case __TEXT__:
                header("Content-Type: text/text; charset=UTF-8");
                break;
            default:
                break;
        }
    }

    public function setHeader($type)
    {
        $this->type = $type;
    }

    public function send($res)
    {
        if ($this->type === __JSON__) {
            $res = json_encode($res);
        }
        echo $res;
    }
}


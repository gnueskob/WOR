<?php


namespace lsb\Libs;

define('__JSON__', 0);
define('__TEXT__', 1);
define('__HTML__', 2);

class Response implements IResponse
{
    public $status = 200;
    public $msg = 'OK';

    public $body;

    public function __construct()
    {
    }

    public function error(string $protocol, string $errorMsg): void
    {
        header("{$protocol} {$errorMsg}");
    }

    public function setResponse(int $status, string $msg = ''): void
    {
        $this->status = $status;
        $this->msg = $msg;
    }

    public function setHeader(string $header, string ...$value): void
    {
        $headerValue = implode('; ', $value);
        header("{$header}: {$headerValue}");
    }

    public function send($data = null): void
    {
        if (!is_null($data)) {
            $this->body = $data;
        }
        echo $this->body;
    }
}

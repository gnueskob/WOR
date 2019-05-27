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

    public function error(string $protocol, int $code, string $msg): void
    {
        header("{$protocol} {$code} {$msg}");
    }

    public function setHeader(string $header, string ...$value): void
    {
        $headerValue = implode('; ', $value);
        header("{$header}: {$headerValue}");
    }

    public function send(bool $isJsonData = false): void
    {
        echo $isJsonData ? json_encode($this->body) : $this->body;
    }
}

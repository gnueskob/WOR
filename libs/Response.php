<?php


namespace lsb\Libs;

define('__JSON__', 0);
define('__TEXT__', 1);
define('__HTML__', 2);

class Response
{
    public $status = 200;
    public $msg = 'OK';

    public $body;
    public $httpContentType;

    public function __construct()
    {
    }

    public function error(string $protocol, int $code, string $msg): void
    {
        header("{$protocol} {$code} {$msg}");
    }

    public function setHeader(string $header, string ...$value): void
    {
        if ($header === 'Content-Type' && in_array('application/json', $value)) {
            $this->httpContentType = 'json';
        }
        $headerValue = implode('; ', $value);
        header("{$header}: {$headerValue}");
    }

    public function send(): void
    {
        $body = $this->body;
        if ($this->httpContentType === 'json') {
            $body = json_encode($body);
        }
        echo $body;
    }
}

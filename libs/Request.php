<?php

namespace lsb\Libs;

class Request
{
    private $params;

    // default http headers
    public $requestMethod;
    public $requestUri;
    public $serverProtocol;
    public $httpContentType;
    public $httpXAccessToken;
    public $body;

    public function __construct()
    {
        $this->params = [];
        foreach ($_SERVER as $key => $value) {
            $this->{$this->toCamelCase($key)} = $value;
        }
        $this->body = $this->getBody();
    }

    private function toCamelCase(string $str): string
    {
        $res = strtolower($str);
        preg_match_all('/_[a-z]/', $res, $matches);

        foreach ($matches[0] as $match) {
            $c = str_replace('_', '', strtoupper($match));
            $res = str_replace($match, $c, $res);
        }

        return $res;
    }

    private function getBody(): array
    {
        $body = file_get_contents('php://input');
        if ($body === "") {
            return [];
        }

        if ($this->httpContentType === 'application/json') {
            $body = json_decode($body, true);
        }

        $jsonReturnMethods = ["GET", "POST", "PUT"];
        if (in_array($this->requestMethod, $jsonReturnMethods)) {
            return $body;
        }
        return [];
    }

    public function getFiles(): array
    {
        return $_FILES;
    }

    public function setParams(array $params): void
    {
        $this->params = array_merge($this->params, $params);
    }

    public function getParams(): array
    {
        return $this->params;
    }
}

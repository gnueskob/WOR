<?php

namespace lsb\Libs;

class Request implements IRequest
{
    private $params;

    // default http headers
    public $requestMethod;
    public $requestUri;
    public $serverProtocol;

    public function __construct()
    {
        $this->params = [];
        foreach ($_SERVER as $key => $value) {
            $this->{$this->toCamelCase($key)} = $value;
        }
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

    public function getBody(): array
    {
        if ($this->requestMethod === "GET") {
            return [];
        }

        $jsonReturnMethods = ["POST", "PUT"];
        if (in_array($this->requestMethod, $jsonReturnMethods)) {
            return json_decode(file_get_contents('php://input'), true);
        }
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

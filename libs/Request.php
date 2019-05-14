<?php

namespace lsb\Libs;

class Request implements IRequest
{
    public function __construct()
    {
        foreach ($_SERVER as $key => $value) {
            $this->{$this->toCamelCase($key)} = $value;
        }
    }

    private function toCamelCase($str)
    {
        $res = strtolower($str);
        preg_match_all('/_[a-z]/', $res, $matches);

        foreach ($matches[0] as $match) {
            $c = str_replace('_', '', strtoupper($match));
            $res = str_replace($match, $c, $res);
        }

        return $res;
    }

    public function getBody()
    {
        if ($this->requestMethod === "GET") {
            return;
        }

        if ($this->requestMethod === "POST"
        ||  $this->requestMethod === "PUT") {
            return json_decode(file_get_contents('php://input'), true);
        }
    }
}

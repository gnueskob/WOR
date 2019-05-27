<?php

namespace lsb\Libs;

interface IResponse
{
    public function send(bool $isJsonData);
}

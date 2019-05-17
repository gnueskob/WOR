<?php

namespace lsb\libs;

interface IContext
{
    public function next(): void;
    public function setHeader(int $code, string $msg): void;
}

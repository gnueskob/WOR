<?php

namespace lsb\Libs;

use Exception;

/**
 * ErrorHandler class
*/
class CtxException extends Exception
{
    public $status;
    public $expose;
    public $msg;

    public function __construct()
    {
        parent::__construct();
        $this->status = 404;
        $this->expose = true;
    }

    public function getErrorMsg(): string
    {
        $msg = $this->expose ? $this->msg : '';
        return "{$this->status} {$msg}";
    }

    /**
     * @param   int     $status
     * @param   string  $msg
     * @throws  CtxException
    */
    private function setError(int $status, string $msg): void
    {
        $this->status = $status;
        $this->msg = $msg;
        throw $this;
    }

    /**
     * @throws  CtxException
    */
    public function unauthenticatedHandler(): void
    {
        $this->setError(401, "Unauthenticated");
    }

    /**
     * @throws  CtxException
     */
    public function defaultRequestHandler(): void
    {
        $this->setError(404, "Not Found");
    }

    /**
     * @throws  CtxException
     */
    public function invalidMethodHandler(): void
    {
        $this->setError(405, "Method Not Allowed");
    }

    /**
     * @throws  CtxException
     */
    public function internalServerErrorHandler(): void
    {
        $this->setError(500, "Internal Server Error");
    }
}

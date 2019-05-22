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

    /* @throws  CtxException */
    public function throwUnauthenticatedError(): void
    {
        $this->setError(401, "Unauthenticated");
    }

    /* @throws  CtxException */
    public function throwDefaultRequestError(): void
    {
        $this->setError(404, "Not Found");
    }

    /* @throws  CtxException */
    public function throwInvalidMethodError(): void
    {
        $this->setError(405, "Method Not Allowed");
    }

    /* @throws  CtxException */
    public function throwInternalServerError(): void
    {
        $this->setError(500, "Internal Server Error");
    }
}

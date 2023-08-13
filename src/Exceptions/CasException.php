<?php
/**
 * Created by PhpStorm.
 * User: chenyihong
 * Date: 16/8/1
 * Time: 18:05
 */

namespace Leo108\Cas\Exceptions;

class CasException extends \Exception
{
    public const INVALID_REQUEST = 'INVALID_REQUEST';
    public const INVALID_TICKET = 'INVALID_TICKET';
    public const INVALID_SERVICE = 'INVALID_SERVICE';
    public const INTERNAL_ERROR = 'INTERNAL_ERROR';
    public const UNAUTHORIZED_SERVICE_PROXY = 'UNAUTHORIZED_SERVICE_PROXY';

    protected string $casErrorCode;

    public function __construct(string $casErrorCode, string $message = '')
    {
        parent::__construct($message === '' ? $casErrorCode : $message);
        $this->casErrorCode = $casErrorCode;
    }

    public function getCasErrorCode(): string
    {
        return $this->casErrorCode;
    }

    public function getCasMsg(): string
    {
        return $this->casErrorCode;
    }
}

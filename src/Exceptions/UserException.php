<?php
/**
 * Created by PhpStorm.
 * User: chenyihong
 * Date: 16/8/8
 * Time: 14:06
 */

namespace Leo108\CAS\Exceptions;

use Exception;

class UserException extends \RuntimeException
{
    public function __construct($message, $code = -1, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

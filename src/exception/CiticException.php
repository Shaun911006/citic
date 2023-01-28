<?php
/**
 * Author:Shaun·Yang
 * Date:2023/1/28
 * Time:上午11:03
 * Description:
 */

namespace citic\exception;

use Throwable;

class CiticException extends \Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
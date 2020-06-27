<?php

namespace Payanyway\Exception;

class PaymentException extends \Exception
{
    public function __construct($message = '', $code = 0, \Exception $previous = null)
    {
        $message = $message ? $message : 'Unknown payment exception.';

        parent::__construct($message, $code, $previous);
    }
}

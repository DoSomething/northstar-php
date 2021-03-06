<?php

namespace DoSomething\Gateway\Exceptions;

class InternalException extends ApiException
{
    /**
     * Make a new internal (likely 500) API response exception.
     * @param string $message
     */
    public function __construct($endpoint, $code, $message)
    {
        parent::__construct($endpoint, $code, $message);
    }
}

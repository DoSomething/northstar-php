<?php

namespace DoSomething\Gateway\Exceptions;

class ForbiddenException extends ApiException
{
    /**
     * Make a new 403 Forbidden API response exception.
     * @param string $message
     */
    public function __construct($endpoint, $message)
    {
        parent::__construct($endpoint, 403, $message);
    }
}

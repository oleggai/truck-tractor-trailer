<?php

namespace ProfileBundle\Exception;

use Exception;

class InvalidUserTypeException extends Exception
{

    public function __construct($message)
    {
        $message = 'Invalid user type: ' . $message;
        parent::__construct($message);
    }
}
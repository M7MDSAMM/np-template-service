<?php

namespace App\Exceptions;

use RuntimeException;

class TemplateInactiveException extends RuntimeException
{
    public function __construct(string $message = 'Template is inactive')
    {
        parent::__construct($message, 409);
    }
}

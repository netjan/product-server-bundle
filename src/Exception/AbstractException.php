<?php

declare(strict_types=1);

namespace NetJan\ProductServerBundle\Exception;

abstract class AbstractException extends \Exception
{
    public function __construct(\Throwable $previous = null)
    {
        parent::__construct($this->getReason(), 0, $previous);
    }

    abstract protected function getReason(): string;
}

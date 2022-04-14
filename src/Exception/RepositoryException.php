<?php

declare(strict_types=1);

namespace NetJan\ProductServerBundle\Exception;

final class RepositoryException extends AbstractException implements ExceptionInterface
{
    protected function getReason(): string
    {
        return 'Błąd zapisywania danych!!';
    }
}

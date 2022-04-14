<?php

namespace NetJan\ProductServerBundle\Tests\Exception;

use PHPUnit\Framework\TestCase;
use NetJan\ProductServerBundle\Exception\ORMException;
use NetJan\ProductServerBundle\Exception\ExceptionInterface;
use NetJan\ProductServerBundle\Exception\RepositoryException;

class ProductExceptionTest extends TestCase
{
    public function exceptionDataProvider(): \Generator
    {
        yield [
            new ORMException(),
            'Błąd zapisywania danych!',
        ];
        yield [
            new RepositoryException(),
            'Błąd zapisywania danych!!',
        ];
    }

    public function classesDataProvider(): array
    {
        return [
            [ORMException::class],
            [RepositoryException::class],
        ];
    }

    /**
     * @dataProvider exceptionDataProvider
     */
    public function testGetMessage(ExceptionInterface $exception, string $message): void
    {
        self::assertSame($message, $exception->getMessage());
    }

    /**
     * @dataProvider exceptionDataProvider
     */
    public function testImplementsExceptionInterface(ExceptionInterface $exception): void
    {
        self::assertInstanceOf(ExceptionInterface::class, $exception);
    }

    /**
     * @dataProvider classesDataProvider
     */
    public function testThrow(string $exception)
    {
        $this->expectException($exception);

        throw new $exception();
    }
}

<?php

namespace NetJan\ProductServerBundle\Tests\Entity;

use NetJan\ProductServerBundle\Entity\Product;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class ProductTest extends TestCase
{
    private Product $entityTest;

    public function setUp(): void
    {
        $this->entityTest = new Product();
    }

    public function testCreateEmptyProduct(): void
    {
        $this->assertInstanceOf(Product::class, $this->entityTest);
        $this->assertNull($this->entityTest->getId());
        $this->assertNull($this->entityTest->getName());
        $this->assertNull($this->entityTest->getAmount());
    }

    public function propertyGetSet(): \Generator
    {
        yield ['name', 'StringValue'];
        yield ['amount', 1];
    }

    /**
     * @dataProvider propertyGetSet
     */
    public function testGetSet(string $propertyName, $expectedValue): void
    {
        $setMethod = 'set'.\ucfirst($propertyName);
        $this->entityTest->$setMethod($expectedValue);
        $getMethod = 'get'.\ucfirst($propertyName);
        $actual = $this->entityTest->$getMethod();
        $this->assertSame($expectedValue, $actual);
        $this->assertEquals($expectedValue, $actual);
    }

    public function propertyAssertProvider(): array
    {
        return [
            ['name', '@Assert\NotBlank'],
            ['name', '@Assert\Type("string")'],
            ['name', '@Assert\Length(max=255)'],
            ['amount', '@Assert\NotBlank'],
            ['amount', '@Assert\Type("integer")'],
            ['amount', '@Assert\GreaterThanOrEqual(0)'],
        ];
    }

    /**
     * @dataProvider propertyAssertProvider
     */
    public function testAssertAnnotationSetOnProperty(string $propertyName, string $expectedAnnotation): void
    {
        $property = new \ReflectionProperty(Product::class, $propertyName);
        $result = $property->getDocComment();

        self::assertStringContainsString(
            $expectedAnnotation,
            $result,
            sprintf('%s::%s does not contain "%s" in the docBlock.', Product::class, $propertyName, $expectedAnnotation)
        );
    }

    public function ormProvider(): array
    {
        return [
            ['__class', '@ORM\Entity(repositoryClass=ProductRepository::class)'],
            ['__class', '@ORM\Table(name="netjan_product")'],
            ['id', '@ORM\Id'],
            ['id', '@ORM\GeneratedValue'],
            ['id', '@ORM\Column(type="integer")'],
            ['name', '@ORM\Column(type="string", length=255)'],
            ['amount', '@ORM\Column(type="integer")'],
        ];
    }

    /**
     * @dataProvider ormProvider
     */
    public function testOrm(string $propertyName, string $expectedAnnotation): void
    {
        if ('__class' === $propertyName) {
            $class = new \ReflectionClass(Product::class);
            $result = $class->getDocComment();
        } else {
            $property = new \ReflectionProperty(Product::class, $propertyName);
            $result = $property->getDocComment();
        }

        self::assertStringContainsString(
            $expectedAnnotation,
            $result,
            sprintf('%s::%s does not contain "%s" in the docBlock.', Product::class, $propertyName, $expectedAnnotation)
        );
    }
}

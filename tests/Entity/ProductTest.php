<?php

namespace NetJan\ProductServerBundle\Tests\Entity;

use NetJan\ProductServerBundle\Entity\Product;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Validator\Validation;

class ProductTest extends TestCase
{
    public function testProductCreate()
    {
        $product = new Product();

        $this->assertEquals(null, $product->getName());
        $this->assertEquals(null, $product->getAmount());

        $product->setName(null);
        $product->setAmount(null);
        $this->assertEquals(null, $product->getName());
        $this->assertEquals(null, $product->getAmount());

        $product->setName("Name");
        $product->setAmount(1);
        $this->assertEquals("Name", $product->getName());
        $this->assertEquals(1, $product->getAmount());
    }

    public function testProductValidation()
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping(true)
            ->addDefaultDoctrineAnnotationReader()
            ->getValidator();

        // name - @Assert\NotBlank
        // amount @Assert\NotBlank
        $product = new Product();
        $errors = $validator->validate($product);
        $this->assertEquals(2, count($errors));

        // name - @Assert\Length(max=255)
        // amount @Assert\NotBlank
        $product->setName(str_pad('A', 256, 'A'));
        $errors = $validator->validate($product);
        $this->assertEquals(2, count($errors));

        // name - @Assert\Length(max=255)
        // amount @Assert\GreaterThanOrEqual(0)
        $product->setAmount(-1);
        $errors = $validator->validate($product);
        $this->assertEquals(2, count($errors));

        // amount @Assert\NotBlank
        $product->setName('Name');
        $product->setAmount(null);
        $errors = $validator->validate($product);
        $this->assertEquals(1, count($errors));

        // name @Assert\NotBlank
        $product->setName(null);
        $product->setAmount(1);
        $errors = $validator->validate($product);
        $this->assertEquals(1, count($errors));

        $product->setName('Name');
        $product->setAmount(1);
        $errors = $validator->validate($product);
        $this->assertEquals(0, count($errors));
    }
}

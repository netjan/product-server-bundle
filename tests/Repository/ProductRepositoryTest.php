<?php

namespace NetJan\ProductServerBundle\Tests\Repository;

use Doctrine\ORM\Tools\SchemaTool;
use NetJan\ProductServerBundle\Entity\Product;
use NetJan\ProductServerBundle\Exception as BundleException;
use NetJan\ProductServerBundle\Filter\ProductFilter;
use NetJan\ProductServerBundle\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProductRepositoryTest extends KernelTestCase
{
    private $manager;
    private ProductRepository $testRepository;

    protected static function getKernelClass(): string
    {
        require_once \dirname(__DIR__).'/Fixtures/TestApp/src/Kernel.php';

        return 'TestApp\Kernel';
    }

    protected function setUp(): void
    {
        static::bootKernel();

        $container = static::getContainer();
        $registry = $container->get('doctrine');
        $this->manager = $registry->getManager();

        $this->configureDatabase();

        $this->testRepository = $this->manager->getRepository(Product::class);
    }

    public function testCreate(): void
    {
        $this->assertInstanceOf(ProductRepository::class, $this->testRepository);
    }

    /**
     * @dataProvider validProduct
     */
    public function testList(Product $product, string $name, int $amount): void
    {
        $filter = new ProductFilter();
        // $filter->stock
        $actual = $this->testRepository->list($filter);
        $actual = count($actual);
        $expected = 0;
        $this->assertSame($expected, $actual);

        $this->testRepository->save($product);

        // amount > 5
        $expected = 0;
        if (5 < $amount) {
            $expected = 1;
        }
        $actual = $this->testRepository->list($filter);
        $actual = count($actual);
        $this->assertSame($expected, $actual);

        // amount > 0
        $filter->stock = true;
        $expected = 0;
        if (0 < $amount) {
            $expected = 1;
        }
        $actual = $this->testRepository->list($filter);
        $actual = count($actual);
        $this->assertSame($expected, $actual);

        // amount = 0
        $filter->stock = false;
        $expected = 0;
        if (0 === $amount) {
            $expected = 1;
        }
        $actual = $this->testRepository->list($filter);
        $actual = count($actual);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider validProduct
     */
    public function testSave(Product $product, string $name, int $amount): void
    {
        $this->testRepository->save($product);

        $actual = $product->getId();
        $expected = null;
        $this->assertNotSame($expected, $actual);

        /**
         * @var Product $savedProduct
         */
        $savedProduct = $this->testRepository->find($product->getId());
        $this->assertNotSame($expected, $savedProduct);

        $actual = $product->getId();
        $expected = $savedProduct->getId();
        $this->assertSame($expected, $actual);

        $actual = $savedProduct->getName();
        $this->assertSame($name, $actual);

        $actual = $savedProduct->getAmount();
        $this->assertSame($amount, $actual);
    }

    public function testThrowBundleExceptionRepositoryExceptionWhenSave()
    {
        $this->expectException(BundleException\RepositoryException::class);
        $this->expectExceptionMessage('Błąd zapisywania danych!!');

        $product = new Product();
        $this->testRepository->save($product);
    }

    public function testThrowBundleExceptionORMExceptionWhenSave()
    {
        $this->expectException(BundleException\ORMException::class);
        $this->expectExceptionMessage('Błąd zapisywania danych!');

        $product = new Product();
        $entityManager = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        $entityManager->close();
        $this->testRepository->save($product);
    }

    /**
     * @dataProvider validProduct
     */
    public function testRemove(Product $product): void
    {
        $products = $this->testRepository->findAll();
        $expected = count($products);
        $this->testRepository->save($product);

        $products = $this->testRepository->findAll();
        $actual = count($products);
        $this->assertSame($expected + 1, $actual);
        $savedId = $this->testRepository->find($product->getId())->getId();
        $this->assertSame($product->getId(), $savedId);

        $this->testRepository->remove($product);
        $products = $this->testRepository->findAll();
        $actual = count($products);
        $this->assertSame($expected, $actual);
        $actual = $this->testRepository->find($savedId);
        $this->assertNull($actual);
    }

    /**
     * @dataProvider validProduct
     */
    public function testThrowBundleExceptionRepositoryExceptionWhenRemove(Product $product)
    {
        $this->expectException(BundleException\RepositoryException::class);
        $this->expectExceptionMessage('Błąd zapisywania danych!!');

        $this->testRepository->save($product);
        $cloneProduct = clone $product;
        $this->testRepository->remove($cloneProduct);
    }

    public function testThrowBundleExceptionORMExceptionWhenRemove()
    {
        $this->expectException(BundleException\ORMException::class);
        $this->expectExceptionMessage('Błąd zapisywania danych!');

        $product = new Product();
        $entityManager = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        $entityManager->close();
        $this->testRepository->remove($product);
    }

    protected function configureDatabase()
    {
        $schema = [
            $this->manager->getClassMetadata(Product::class),
        ];

        $schemaTool = new SchemaTool($this->manager);
        $schemaTool->dropSchema($schema);
        $schemaTool->createSchema($schema);
    }

    public function validProduct(): \Generator
    {
        $products = [
            [ 'Product name', 1 ],
            [ 'Product name', 0 ],
            [ 'Product name', 6 ],
        ];

        foreach ($products as $item) {
            yield [$this->getProduct($item[0], $item[1]), $item[0], $item[1]];
        }
    }

    public function getProduct(string $name, int $amount): Product
    {
        $product = new Product();
        $product->setName($name);
        $product->setAmount($amount);

        return $product;
    }
}

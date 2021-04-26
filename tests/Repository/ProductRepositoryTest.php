<?php

namespace NetJan\ProductServerBundle\Tests\Repository;

use Doctrine\ORM\Tools\SchemaTool;
use NetJan\ProductServerBundle\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProductRepositoryTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        require_once __DIR__ . '/../Fixtures/App/src/Kernel.php';

        return 'App\Kernel';
    }

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $schema = [
            $this->entityManager->getClassMetadata(Product::class),
        ];

        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropSchema($schema);
        $schemaTool->createSchema($schema);
    }

    public function testEmptyBase()
    {
        $productRepository = $this->entityManager->getRepository(Product::class);
        $list = $productRepository->getList();

        $this->assertSame([], $list);

        $list = $productRepository->getList([
            'stock' => true,
        ]);

        $this->assertSame([], $list);

        $list = $productRepository->getList([
            'stock' => false,
        ]);

        $this->assertSame([], $list);
    }

    public function testSaveEmpty()
    {
        $product = new Product();
        $productRepository = $this->entityManager->getRepository(Product::class);
        $result = $productRepository->save($product);
        $this->assertSame(true, $result['error']);
    }

    public function testSave()
    {
        $product = new Product();
        $productRepository = $this->entityManager->getRepository(Product::class);

        $product->setName('name');
        $product->setAmount(1);
        $result = $productRepository->save($product);
        $this->assertSame(false, $result['error']);

        // amount > 0
        $list = $productRepository->getList([
            'stock' => true,
        ]);
        $this->assertSame(1, count($list));

        $product = clone $product;
        $product->setAmount(0);
        $result = $productRepository->save($product);
        $this->assertSame(false, $result['error']);

        // amount = 0
        $list = $productRepository->getList([
            'stock' => false,
        ]);
        $this->assertSame(1, count($list));

        $product = clone $product;
        $product->setAmount(6);
        $result = $productRepository->save($product);
        $this->assertSame(false, $result['error']);

        // amount > 5
        $list = $productRepository->getList();
        $this->assertSame(1, count($list));

        // amount > 0
        $list = $productRepository->getList([
            'stock' => true,
        ]);
        $this->assertSame(2, count($list));

        $product = clone $product;
        $product->setAmount(5);
        $result = $productRepository->save($product);
        $this->assertSame(false, $result['error']);

        // amount > 5
        $list = $productRepository->getList();
        $this->assertSame(1, count($list));

        // amount > 0
        $list = $productRepository->getList([
            'stock' => true,
        ]);
        $this->assertSame(3, count($list));

        // amount = 0
        $list = $productRepository->getList([
            'stock' => false,
        ]);
        $this->assertSame(1, count($list));

        // total records
        $list = $productRepository->findAll();
        $this->assertSame(4, count($list));
    }

    public function testSaveAndRemove()
    {
        $product = new Product();
        $productRepository = $this->entityManager->getRepository(Product::class);

        $product->setName('name');
        $product->setAmount(1);
        $result = $productRepository->save($product);
        $this->assertSame(false, $result['error']);

        $list = $productRepository->getList([
            'stock' => true,
        ]);
        $this->assertSame(1, count($list));

        $result = $productRepository->remove($product);
        $this->assertSame(false, $result['error']);

        $list = $productRepository->getList([
            'stock' => true,
        ]);
        $this->assertSame(0, count($list));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // doing this is recommended to avoid memory leaks
        $this->entityManager->close();
        $this->entityManager = null;
    }
}

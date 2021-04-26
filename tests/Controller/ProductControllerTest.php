<?php

namespace NetJan\ProductServerBundle\Tests\Tests\Controller;

use Doctrine\ORM\Tools\SchemaTool;
use Nelmio\Alice\Loader\NativeLoader;
use NetJan\ProductServerBundle\Entity\Product;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProductControllerTest extends WebTestCase
{
    protected $client;

    protected static function getKernelClass(): string
    {
        require_once __DIR__ . '/../Fixtures/App/src/Kernel.php';

        return 'App\Kernel';
    }

    protected function setUp(): void
    {
        $this->client = self::createClient();

        $em = $this->client->getContainer()->get('doctrine')->getManager();
        $schema = [
            $em->getClassMetadata(Product::class),
        ];
        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema($schema);
        $schemaTool->createSchema($schema);

        parent::setUp();
    }

    public function testGet404()
    {
        $this->client->request('GET', '/api/products/non-existant');
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function test200()
    {
        $this->client->request('GET', '/api/products');
        $this->assertResponseStatusCodeSame(200);
    }

    public function testCollectionOperations()
    {
        $loader = new NativeLoader();
        $objectSet = $loader->loadData([
            Product::class => [
                'product{1..20}' => [
                    'name' => '<name()>',
                    'amount' => '<numberBetween(0, 10)>'
                ],
            ]
        ]);

        $stockTrue = 0;
        $stockFalse = 0;
        $stock5 = 0;
        $randItem = null;
        $i = rand(1, 20);
        foreach ($objectSet->getObjects() as $key => $product) {
            if (0 == $product->getAmount()) {
                $stockFalse++;
            } else {
                $stockTrue++;
                if (5 < $product->getAmount()) {
                    $stock5++;
                }
            }

            $submit_data = [
                'name' => $product->getName(),
                'amount' => $product->getAmount(),
            ];
            $this->client->request(
                'POST',
                '/api/products',
                [],
                [],
                [
                    'CONTENT_TYPE' => 'application/json'
                ],
                \json_encode($submit_data)
            );
            $this->assertResponseIsSuccessful();

            $content = $this->client->getResponse()->getContent();
            $result = \json_decode((string) $content, true);
            if ($key == 'product' . $i) {
                $randItem = [
                    $submit_data,
                    $result,
                ];
            }
        }

        $this->client->request('GET', '/api/products');
        $this->assertResponseStatusCodeSame(200);
        $content = $this->client->getResponse()->getContent();
        $result = \json_decode((string) $content, true);
        $this->assertEquals($stock5, count($result));

        $this->client->request('GET', '/api/products?stock=true');
        $this->assertResponseStatusCodeSame(200);
        $content = $this->client->getResponse()->getContent();
        $result = \json_decode((string) $content, true);
        $this->assertEquals($stockTrue, count($result));

        $this->client->request('GET', '/api/products?stock=false');
        $this->assertResponseStatusCodeSame(200);
        $content = $this->client->getResponse()->getContent();
        $result = \json_decode((string) $content, true);
        $this->assertEquals($stockFalse, count($result));

        foreach ($randItem[0] as $key => $value) {
            $this->assertEquals($value, $randItem[1][$key]);
        }
        $this->client->request('GET', '/api/products/' . $randItem[1]['id']);
        $this->assertResponseStatusCodeSame(200);
        $content = $this->client->getResponse()->getContent();
        $result = \json_decode((string) $content, true);
        foreach ($randItem[0] as $key => $value) {
            $this->assertEquals($value, $result[$key]);
        }
    }

    public function testItemOperations()
    {
        $submit_data = [
            'name' => 'Name',
            'amount' => 1,
        ];
        $this->client->request(
            'POST',
            '/api/products',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json'
            ],
            \json_encode($submit_data)
        );
        $this->assertTestItemOperations($submit_data);

        $submit_data = [
            'name' => 'Name Name',
            'amount' => 2,
        ];
        $this->client->request(
            'PUT',
            '/api/products/1',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json'
            ],
            \json_encode($submit_data)
        );
        $this->assertTestItemOperations($submit_data);

        $submit_data = [
            'name' => 'Name Name Name',
            'amount' => 2,
        ];
        $this->client->request(
            'PATCH',
            '/api/products/1',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json'
            ],
            \json_encode(['name' => $submit_data['name']])
        );
        $this->assertTestItemOperations($submit_data);

        $submit_data = [
            'name' => 'Name Name Name',
            'amount' => 3,
        ];
        $this->client->request(
            'PATCH',
            '/api/products/1',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json'
            ],
            \json_encode(['amount' => $submit_data['amount']])
        );
        $this->assertTestItemOperations($submit_data);
    }

    private function assertTestItemOperations($submit_data)
    {
        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        $result = \json_decode((string) $content, true);

        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('amount', $result);

        $this->assertEquals(1, $result['id']);
        foreach ($submit_data as $key => $value) {
            $this->assertEquals($value, $result[$key]);
        }
    }
}

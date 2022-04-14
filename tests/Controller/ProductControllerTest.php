<?php

namespace NetJan\ProductServerBundle\Tests\Tests\Controller;

use Doctrine\ORM\Tools\SchemaTool;
use Nelmio\Alice\Loader\NativeLoader;
use NetJan\ProductServerBundle\Entity\Product;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\HttpFoundation\Exception\JsonException;

class ProductControllerTest extends WebTestCase
{
    protected $client;

    protected static function getKernelClass(): string
    {
        require_once __DIR__ . '/../Fixtures/TestApp/src/Kernel.php';

        return 'TestApp\Kernel';
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

    public function getUrls(): ?\Generator
    {
            yield ['GET', '/api/products', Response::HTTP_OK];
            yield ['POST', '/api/products', Response::HTTP_BAD_REQUEST];
    }

    /**
     * @dataProvider getUrls
     */
    public function testUrls(string $method, string $uri, int $statusCode): void
    {
        $this->client->catchExceptions(false);
        $this->client->request($method, $uri);

        $this->assertResponseStatusCodeSame($statusCode, sprintf('The %s URL loads correctly.', $uri));
    }

    public function testGet404()
    {
        $this->client->request('GET', '/api/products/non-existant');
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
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

            $submitData = [
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
                \json_encode($submitData)
            );
            $this->assertResponseIsSuccessful();

            $content = $this->client->getResponse()->getContent();
            $result = \json_decode((string) $content, true);
            if ($key == 'product' . $i) {
                $randItem = [
                    $submitData,
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
        $submitData = [
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
            \json_encode($submitData)
        );
        $this->assertTestItemOperations($submitData);

        $submitData = [
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
            \json_encode($submitData)
        );
        $this->assertTestItemOperations($submitData);

        $submitData = [
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
            \json_encode(['name' => $submitData['name']])
        );
        $this->assertTestItemOperations($submitData);

        $submitData = [
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
            \json_encode(['amount' => $submitData['amount']])
        );
        $this->assertTestItemOperations($submitData);
    }

    private function assertTestItemOperations($submitData)
    {
        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        $result = \json_decode((string) $content, true);

        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('amount', $result);

        $this->assertEquals(1, $result['id']);
        foreach ($submitData as $key => $value) {
            $this->assertEquals($value, $result[$key]);
        }
    }

    /**
     * @dataProvider invalidData
     */
    public function testFormErrors(?string $name, $amount, int $expectedCode, array $expectedResult)
    {
        $submitData = [
            'name' => $name,
            'amount' => $amount,
        ];

        $this->client->request(
            'POST',
            '/api/products',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json'
            ],
            \json_encode($submitData)
        );

        $this->assertResponseStatusCodeSame($expectedCode);
        $content = $this->client->getResponse()->getContent();
        $result = \json_decode((string) $content, true);
        $this->assertSame($expectedResult, $result);
    }

    public function invalidData(): array
    {
        $longText = ''
            .'1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890'
            .'1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890'
            .'12345678901234567890123456789012345678901234567890123456';
        return [
            ['', null, 400, ['This value should not be blank.', 'This value should not be blank.']],
            [null, null, 400, ['This value should not be blank.', 'This value should not be blank.']],
            [null, -1, 400, ['This value should not be blank.', 'This value should be greater than or equal to 0.']],
            [null, 'ss', 400, ['This value should not be blank.', 'This value is not valid.']],
            ['ssss', null, 400, ['This value should not be blank.']],
            ['', 1, 400, ['This value should not be blank.']],
            [$longText, 1, 400, ['This value is too long. It should have 255 characters or less.']],
        ];
    }

    /**
     * @dataProvider emptyData
     */
    public function testRequestEmptyData(string $method, string $id, int $expectedCode, $expectedResult)
    {
        $validData = [
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
            \json_encode($validData)
        );
        $this->assertTestItemOperations($validData);

        $this->client->request(
            $method,
            '/api/products'.$id
        );

        $this->assertResponseStatusCodeSame($expectedCode);
        $content = $this->client->getResponse()->getContent();
        $result = \json_decode((string) $content, true);
        $this->assertSame($expectedResult, $result);
    }

    public function emptyData(): array
    {
        return [
            ['POST', '', 400, []],
            ['PUT', '/1', 400, []],
            ['PATCH', '/1', 400, []],
            ['DELETE', '/1', 204, null],
        ];
    }
}

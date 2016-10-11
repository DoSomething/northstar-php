<?php

use GuzzleHttp\Psr7\Response;

class AuthorizesWithNorthstarTest extends TestCase
{
    protected $defaultConfig = [
        'grant' => 'client_credentials',
        'url' => 'https://northstar-phpunit.dosomething.org', // not a real server!
        'bridge' => MockOAuthBridge::class,

        // Then, configure client ID, client secret, and scopes per grant.
        'client_credentials' => [
            'client_id' => 'example',
            'client_secret' => 'secret1',
            'scope' => ['user'],
        ],
    ];

    /**
     * Test that we can instantiate a client that authorizes with Northstar.
     * @skip
     */
    public function testNorthstarApiClient()
    {
        $restClient = new MockNorthstar($this->defaultConfig, [/* ... */]);

        $this->assertEquals((string) $restClient->getBaseUri(), 'https://northstar-phpunit.dosomething.org');
    }

    /**
     * Test that we can request an access token and use it to retrieve
     * a page using client credentials grant.
     */
    public function testGetAuthorizedPage()
    {
        $restClient = new MockNorthstar($this->defaultConfig, [
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'token_type' => 'Bearer',
                'access_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6ImUwNmJjMDhlZGE5NjRiZmQwNjYzZjc5MzgxNDhkZ
                TYzYjYzNzkxYmMxOTFhNTEwYmY4MDZlYzJhMGZiNjUzYzc1MjFhOWIzYTFmM2NjNjcyIn0.eyJpc3MiOiJodHRwOlwvXC9ub3J0aHN0Y
                XIuZGV2OjgwMDAiLCJhdWQiOiJ0cnVzdGVkLXRlc3QtY2xpZW50IiwianRpIjoiZTA2YmMwOGVkYTk2NGJmZDA2NjNmNzkzODE0OGRlN
                jNiNjM3OTFiYzE5MWE1MTBiZjgwNmVjMmEwZmI2NTNjNzUyMWE5YjNhMWYzY2M2NzIiLCJpYXQiOjE0NzYyMDEzMzEsIm5iZiI6MTQ3N
                jIwMTMzMSwiZXhwIjoxNDc2MjA0OTMxLCJzdWIiOiIiLCJyb2xlIjoiIiwic2NvcGVzIjpbXX0.aFyfvihTvumdWPgtfXxKQZYKVGJw-
                DnvUZO-XXNUgKYy8ngq0OTHMV00_VEqJVHPZVZ1FQv7sq-LcxpSpEM-VgfPsNUVuhJ8E3nX63ntTQQiv_CFBvippb1LJqVhfE7gzaKQc
                eatw5dT25nDlzRQIrUY1kz1exGHUozvkHAeJ_g',
                'expires_in' => 3600,
            ])),
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'status' => 'good',
            ])),
        ]);

        $response = $restClient->get('status');
        $this->assertEquals($response, ['status' => 'good']);
    }

    /**
     * Test that we can use the "all users" endpoint.
     */
    public function testGetAllUsers()
    {
        $restClient = new MockNorthstar($this->defaultConfig, [
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'token_type' => 'Bearer',
                'access_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6ImUwNmJjMDhlZGE5NjRiZmQwNjYzZjc5MzgxNDhkZ
                TYzYjYzNzkxYmMxOTFhNTEwYmY4MDZlYzJhMGZiNjUzYzc1MjFhOWIzYTFmM2NjNjcyIn0.eyJpc3MiOiJodHRwOlwvXC9ub3J0aHN0Y
                XIuZGV2OjgwMDAiLCJhdWQiOiJ0cnVzdGVkLXRlc3QtY2xpZW50IiwianRpIjoiZTA2YmMwOGVkYTk2NGJmZDA2NjNmNzkzODE0OGRlN
                jNiNjM3OTFiYzE5MWE1MTBiZjgwNmVjMmEwZmI2NTNjNzUyMWE5YjNhMWYzY2M2NzIiLCJpYXQiOjE0NzYyMDEzMzEsIm5iZiI6MTQ3N
                jIwMTMzMSwiZXhwIjoxNDc2MjA0OTMxLCJzdWIiOiIiLCJyb2xlIjoiIiwic2NvcGVzIjpbXX0.aFyfvihTvumdWPgtfXxKQZYKVGJw-
                DnvUZO-XXNUgKYy8ngq0OTHMV00_VEqJVHPZVZ1FQv7sq-LcxpSpEM-VgfPsNUVuhJ8E3nX63ntTQQiv_CFBvippb1LJqVhfE7gzaKQc
                eatw5dT25nDlzRQIrUY1kz1exGHUozvkHAeJ_g',
                'expires_in' => 3600,
            ])),
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'data' => [
                    [
                        'id' => '5480c950bffebc651c8b456f',
                        'first_name' => 'John',
                        'last_name' => 'Doe',
                    ],
                    [
                        'id' => '5480c950ce5fbc2145eb7721',
                        'first_name' => 'Jane',
                        'last_name' => 'Smith',
                    ],
                ],
                'meta' => [
                    'pagination' => [
                        'total' => 65,
                        'count' => 20,
                        'per_page' => 15,
                        'current_page' => 1,
                        'total_pages' => 5,
                        'links' => [
                            'next' => 'https://northstar.dosomething.org/v1/users?page=2',
                        ],
                    ],
                ],
            ])),
        ]);

        $response = $restClient->getAllUsers();

        // It should successfully serialize into a collection.
        $this->assertInstanceOf(\DoSomething\Gateway\Common\ApiCollection::class, $response);

        // And we should be able to traverse and read values from that.
        $this->assertEquals('John', $response[0]->first_name);
        $this->assertEquals('Jane', $response[1]->first_name);
        $this->assertEquals(2, $response->count());
    }
}

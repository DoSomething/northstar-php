<?php

use DoSomething\GatewayTests\Helpers\JsonResponse;

class ForwardsTransactionIdsTest extends TestCase
{
    /** @test */
    public function it_should_set_new_id()
    {
        $client = new ForwardsTransactionIdsClient([new JsonResponse()]);

        $response = $client->get('/hello');

        // We should see a `X-Request-Id` on the request.
        $this->assertArrayHasKey('X-Request-ID', $client->getLastRequest()->getHeaders());
    }

    /** @test */
    public function it_should_increment_existing_ids()
    {
        // Let's pretend that we are handling a request with an `X-Request-Id`
        // header attached to it (generated by another internal service).
        $this->withRequestHeader('X-Request-ID', '1515433053.7233-0');

        $client = new ForwardsTransactionIdsClient([new JsonResponse()]);
        $response = $client->get('/hello');

        // We should see the incremented `X-Request-Id` on the request.
        $this->assertEquals(['1515433053.7233-1'], $client->getLastRequest()->getHeader('X-Request-ID'));
    }
}
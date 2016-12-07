<?php

use DoSomething\GatewayTests\Helpers\Gambit\SignupResponse;
use DoSomething\GatewayTests\Helpers\Gambit\CampaignResponse;
use DoSomething\GatewayTests\Helpers\Gambit\CampaignsResponse;
use DoSomething\GatewayTests\Helpers\Gambit\CampaignMessageResponse;

class GambitTest extends PHPUnit_Framework_TestCase
{
    protected $defaultConfig = [
        'url' => 'https://gambit-phpunit.dosomething.org', // not a real server!
    ];
    protected $authorizedConfig = [
        'url'    => 'https://gambit-phpunit.dosomething.org', // not a real server!
        'apiKey' => 'gambit_api_key',
    ];

    /**
     * Test that we can use the all campaigns endpoint.
     */
    public function testGetAllCampaigns()
    {
        $restClient = new MockGambit($this->defaultConfig, [
            new CampaignsResponse,
        ]);

        $campaigns = $restClient->getAllCampaigns();

        // It should successfully serialize into a collection.
        $this->assertInstanceOf(\DoSomething\Gateway\Common\ApiCollection::class, $campaigns);

        // Test correct campaigns count.
        $this->assertEquals(2, $campaigns->count());

        // And we should be able to traverse and read values from that.
        $this->assertEquals('World Recycle Week: Close The Loop', $campaigns[0]->title);
        $this->assertEquals('Trash Stash', $campaigns[1]->title);
    }

    /**
     * Test that we can retrieve a campaign by their ID.
     */
    public function testGetCampaignById()
    {
        $restClient = new MockGambit($this->defaultConfig, [
            new CampaignResponse,
        ]);
        $campaign = $restClient->getCampaign(876);

        // id
        $this->assertSame(876, $campaign->id);

        // title
        $this->assertEquals('Trash Stash', $campaign->title);

        // campaignbot
        $this->assertSame(true, $campaign->campaignbot);

        // status
        $this->assertEquals('active', $campaign->status);

        // current_run
        $this->assertSame(6230, $campaign->current_run);

        // mobilecommons_group_doing
        $this->assertSame(258142, $campaign->mobilecommons_group_doing);

        // mobilecommons_group_completed
        $this->assertSame(258163, $campaign->mobilecommons_group_completed);

        // keywords
        $this->assertInternalType('array', $campaign->keywords);
        $this->assertContainsOnly('string', $campaign->keywords);
        $this->assertEquals(['TRASHBOT'], $campaign->keywords);
    }

    /**
     * Test that we can post a campaign message.
     */
    public function testCreateCampaignMessage()
    {
        // Input data.
        $payload = [
            'id' => 46,
            'phone' => '5555555511',
            'type' => 'scheduled_relative_to_signup_date',
        ];

        // Mock response.
        $restClient = new MockGambit($this->authorizedConfig, [
            new CampaignMessageResponse($payload),
        ]);

        // Call the endpoint.
        $result = $restClient->createCampaignMessage(
          $payload['id'],
          $payload['phone'],
          $payload['type']
        );

        // Assert result.
        $this->assertTrue($result);
    }

    /**
     * Test that we can post a signup.
     */
    public function testCreateSignup()
    {
        $restClient = new MockGambit($this->authorizedConfig, [
            new SignupResponse,
        ]);
        $result = $restClient->createSignup(2309260, 'node/1141');
        $this->assertTrue($result);
    }
}

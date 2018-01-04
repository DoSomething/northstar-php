<?php

use DoSomething\Gateway\Testing\WithGatewayMocks;

class WithGatewayMocksTest extends TestCase
{
    use WithGatewayMocks;

    /** @test */
    public function testMakeNorthstarUserWithId()
    {
        $user = $this->makeNorthstarUser(['id' => '5543dfd6469c64ec7d8b46b3']);

        // The generated user should be consistent across test runs:
        $this->assertEquals('5543dfd6469c64ec7d8b46b3', $user->id);
        $this->assertEquals('Zoie', $user->first_name);
        $this->assertEquals('Koepp', $user->last_name);
    }

    /** @test */
    public function testMakeNorthstarUserWithEmail()
    {
        $user = $this->makeNorthstarUser(['email' => 'test@example.com']);

        // The generated user should be consistent across test runs:
        $this->assertEquals('572d2180adec364795d42f36', $user->id);
        $this->assertEquals('Brandyn', $user->first_name);
        $this->assertEquals('Mitchell', $user->last_name);
    }
}

<?php

namespace DoSomething\Gateway\Testing;

use Mockery;
use DoSomething\Gateway\Northstar;
use DoSomething\Gateway\Resources\NorthstarUser;
use DoSomething\Gateway\Laravel\LaravelNorthstar;

/**
 * @mixin \Illuminate\Foundation\Testing\TestCase
 */
trait WithGatewayMocks
{
    /**
     * Create a fake Northstar user.
     */
    protected function makeNorthstarUser($attributes)
    {
        $faker = \Faker\Factory::create();

        // Always return the same data based on the requested ID:
        $indexes = array_only($attributes, ['id', 'email', 'mobile', '_id']);
        $key = array_first(array_keys($indexes));
        $faker->seed($indexes[$key]);

        // Generate a fake ObjectID in case we need one.
        $time = $faker->dateTimeBetween('1/1/2015', '1/1/2018')->getTimestamp();
        $prefix = str_pad(dechex($time), 8, '0', STR_PAD_LEFT);
        $random = substr($faker->sha256, 0, 16);
        $objectId = $prefix . $random;

        // Return a fake user based on the given ID:
        return new NorthstarUser(array_merge([
            'id' => $objectId,
            'first_name' => $faker->firstName,
            'last_name' => $faker->lastName,
        ], $attributes));
    }

    /**
     * Configure mocks for Northstar resources.
     */
    protected function configureNorthstarMock()
    {
        $this->app->singleton(Northstar::class, function () {
            $mock = Mockery::mock(LaravelNorthstar::class, config('services.northstar'));
            $mock->makePartial();

            $mock->shouldReceive('getUser')->andReturnUsing(function ($type, $id) {
                return $this->makeNorthstarUser([$type => $id]);
            });

            return $mock;
        });
    }
}

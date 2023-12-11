<?php

namespace Tests;

use Faker\Generator;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected Generator $faker;
    protected Repository $config;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var Repository $config */
        $config = $this->app->get(Repository::class);
        $this->config = $config;

        /** @var Generator $faker */
        $faker = $this->app->get(Generator::class);
        $this->faker = $faker;
    }
}

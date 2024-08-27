<?php

namespace Tests;

use Laragear\ExpireRoute\ExpireRouteServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [ExpireRouteServiceProvider::class];
    }
}

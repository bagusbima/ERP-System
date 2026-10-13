<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Execute a request against the application.
     *
     * A real deployment boots a fresh application for every request, which
     * discards the resolved auth guards. A test case reuses a single
     * application instance, so Sanctum's RequestGuard would otherwise keep
     * returning the user from the previous request when a test switches
     * tokens. Flushing the guards restores production behaviour.
     *
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $cookies
     * @param  array<string, mixed>  $files
     * @param  array<string, mixed>  $server
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app->make('auth')->forgetGuards();

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }
}

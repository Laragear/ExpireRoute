<?php

namespace Laragear\ExpireRoute;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class ExpireRouteServiceProvider extends ServiceProvider
{
    /**
     * Boot the application services.
     */
    public function boot(Router $router): void
    {
        $router->aliasMiddleware(Http\Middleware\Expires::SIGNATURE, Http\Middleware\Expires::class);
    }
}

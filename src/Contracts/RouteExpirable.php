<?php

namespace Laragear\ExpireRoute\Contracts;

use DateTimeInterface;

interface RouteExpirable
{
    /**
     * Returns the moment in time the route with this model should be considered expired.
     */
    public function routeExpiresAt(): DateTimeInterface;
}

<?php

namespace Psa\Core\Web\Actions;

class RouteNotFoundAction
{
    public function run()
    {
        header('HTTP/1.0 404 Not Found');
        return ['message' => 'Route not found'];
    }
}
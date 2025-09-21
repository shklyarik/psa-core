<?php

namespace Psa\Core\Web;

use Psa\Core\Common\Container;

class App
{
    public function __construct(
        protected Container $di,
        protected Router $router,
    )
    {

    }

    public function run()
    {
        $this->router->run();
    }
}

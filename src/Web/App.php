<?php

namespace Psa\Core\Web;

use Psa\Core\Common\Container;

class App
{
    public function __construct(
        protected Container $di,
    )
    {

    }
}

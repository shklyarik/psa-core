<?php

namespace Psa\Base\Web;

use Psa\Base\Common\Container;

class App
{
    public function __construct(
        protected Container $di,
    )
    {

    }
}

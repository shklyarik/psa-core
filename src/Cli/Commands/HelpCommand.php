<?php

namespace Psa\Core\Cli\Commands;

use Psa\Core\Cli\App;

class HelpCommand
{
    public function run(App $app)
    {
        print_r($app->dispatcher->getCommands());
    }
}
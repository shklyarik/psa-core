<?php

namespace Psa\Core\Cli\Commands;

use Psa\Core\Cli\App;

class HelpCommand
{
    public function run(App $app)
    {
        $commands = $app->dispatcher->getCommands();

        $groups = [];
        $standalone = [];

        foreach ($commands as $command => $action) {
            if ($command === 'help') {
                continue;
            }

            if (strpos($command, ':') !== false) {
                [$group, $sub] = explode(':', $command, 2);
                $groups[$group][] = $sub;
            } else {
                $standalone[] = $command;
            }
        }

        foreach ($standalone as $cmd) {
            echo $cmd . "\n";
        }

        echo "\n";

        foreach ($groups as $group => $subcommands) {
            echo $group . ":\n";
            foreach ($subcommands as $sub) {
                echo "  - " . $group . ":" . $sub . "\n";
            }
            echo "\n";
        }
    }
}
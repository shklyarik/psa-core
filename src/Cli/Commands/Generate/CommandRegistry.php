<?php

namespace Psa\Core\Cli\Commands\Generate;

class CommandRegistry
{
    public const Commands = [
        'g:action'  => GenerateAction::class,
        'g:command' => GenerateCommand::class,
    ];
}

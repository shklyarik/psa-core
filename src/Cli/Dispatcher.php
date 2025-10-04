<?php

namespace Psa\Core\Cli;

class Dispatcher
{
    private array $args = [];
    private string $name = 'help';

    public function __construct(
        protected array $commands
    )
    {
        foreach ($_SERVER['argv'] as $key => $arg) {
            if ($key > 0 && $key == 1) {
                $this->name = $arg;
            } else if ($key > 1) {
                $this->args[] = $arg;
            }
        }
    }

    public function getCommandClass()
    {
        if (array_key_exists($this->name, $this->commands)) {
            return $this->commands[$this->name];
        } else {
            echo "The command `" . $this->name . "` not found" . PHP_EOL;
        }
    }

    public function getCommands()
    {
        return $this->commands;
    }
}
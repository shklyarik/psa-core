<?php

namespace Psa\Core\Common;

use RuntimeException;

trait AppTrait
{
    /**
     * Resolve alias into a real filesystem path
     *
     * @param string $path
     * @return string
     * @throws RuntimeException
     */
    public function getAlias(string $path): string
    {
        foreach ($this->alias as $name => $realPath) {
            if (str_starts_with($path, $name)) {
                return $realPath . substr($path, strlen($name));
            }
        }

        throw new RuntimeException("Unknown alias: {$path}");
    }
}
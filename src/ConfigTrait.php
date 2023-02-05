<?php declare(strict_types=1);

namespace jschreuder\MiddleDi;

use ArrayAccess;
use OutOfBoundsException;

trait ConfigTrait
{
    private array | ArrayAccess $config;

    protected function config(string $valueName): mixed
    {
        if (!isset($this->config[$valueName])) {
            throw new OutOfBoundsException('No such config value: ' . $valueName);
        }

        return $this->config[$valueName];
    }
}

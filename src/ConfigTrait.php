<?php declare(strict_types=1);

namespace jschreuder\MiddleDi;

use ArrayAccess;
use OutOfBoundsException;

trait ConfigTrait
{
    private array|ArrayAccess $config;

    public function __construct(array|ArrayAccess $config)
    {
        $this->config = $config;
    }

    public function config(string $valueName): mixed
    {
        $keyExists =
            $this->config instanceof ArrayAccess
                ? $this->config->offsetExists($valueName)
                : \array_key_exists($valueName, $this->config);

        if (!$keyExists) {
            throw new OutOfBoundsException(
                "No such config value: {$valueName}",
            );
        }

        return $this->config[$valueName];
    }
}

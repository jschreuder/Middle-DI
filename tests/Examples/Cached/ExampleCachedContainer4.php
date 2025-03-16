<?php declare(strict_types=1);

namespace Tests\Examples;

class ExampleCachedContainer4 extends \stdClass
{
    private array $__services = [];

    private function __service(string $method, ?string $instanceName = null)
    {
        $suffix = is_null($instanceName) ? '' : '.' . $instanceName;
        return $this->__services[$method . $suffix] ?? ($this->__services[$method . $suffix] = parent::{$method}($instanceName));
    }
} 
<?php declare(strict_types=1);

namespace Tests\Examples;

class ExampleCachedContainer2 extends \stdClass
{
    private array $__services = [];

    private function __service(string $method, ?string $instanceName = null)
    {
        $suffix = $instanceName === null ? "" : "." . $instanceName;
        return $this->__services[$method . $suffix] ??
            ($this->__services[$method . $suffix] = parent::{$method}(
                $instanceName,
            ));
    }
}

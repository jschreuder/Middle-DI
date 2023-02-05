<?php declare(strict_types=1);

namespace spec\jschreuder\MiddleDi\Examples;

class ExampleCachedContainer2
{
    public function getService(): stdClass
    {
        $obj = new \stdClass();
        return $obj;
    }
}

class ExampleCachedContainer2_Compiled extends ExampleCachedContainer2
{
    private array $__services = [];

    private function __service(string $method, ?string $instanceName = null)
    {
        $suffix = is_null($instanceName) ? '' : '.' . $instanceName;
        return $this->__services[$method . $suffix] ?? ($this->__services[$method . $suffix] = parent::{$method}($instanceName));
    }

    public function getService(): stdClass
    {
        return $this->__service('getService');
    }
}

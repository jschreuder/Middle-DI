<?php declare(strict_types=1);

namespace spec\jschreuder\MiddleDi\Examples;

class ExampleCachedContainer1
{
    public function getService(): stdClass
    {
        $obj = new \stdClass();
        return $obj;
    }
}

class ExampleCachedContainer1_Compiled extends ExampleCachedContainer1
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

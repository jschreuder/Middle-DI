<?php declare(strict_types=1);

namespace spec\jschreuder\MiddleDi\Examples;

use stdClass;

class ExampleFaultyContainer1
{
    public function getService(?string $name = 'the-second-param-is-not-allowed', array $settings): stdClass
    {
        $obj = new \stdClass();
        return $obj;
    }
}

class ExampleFaultyContainer2
{
    public function getService(string $name = 'without-returntype-is-not-allowed')
    {
        $obj = new \stdClass();
        return $obj;
    }
}

class ExampleFaultyContainer3
{
    public function getService(array $config): stdClass
    {
        $obj = new \stdClass();
        return $obj;
    }
}

<?php declare(strict_types=1);

namespace Tests\Examples;

use ArrayObject;
use stdClass;

// Should error on having 2 parameters
class ExampleFaultyContainer1
{
    public function getService(
        ?string $name = "the-second-param-is-not-allowed",
        array $settings = [],
    ): stdClass {
        $obj = new stdClass();
        return $obj;
    }
}

// Should error on lack of return-type
class ExampleFaultyContainer2
{
    public function getService(
        string $name = "without-returntype-is-not-allowed",
    ) {
        $obj = new stdClass();
        return $obj;
    }
}

// Should error on wrong first parameter
class ExampleFaultyContainer3
{
    public function getService(array $config): stdClass
    {
        $obj = new stdClass();
        return $obj;
    }
}

// Should error on intersection return type
class ExampleFaultyContainer4
{
    public function getService(array $config): stdClass|ArrayObject
    {
        $obj = new stdClass();
        return $obj;
    }
}

// Should error on scalar return type
class ExampleFaultyContainer5
{
    public function getService(array $config): string
    {
        return "disallowed";
    }
}

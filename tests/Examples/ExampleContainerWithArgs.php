<?php declare(strict_types=1);

namespace Tests\Examples;

class ExampleContainerWithArgs
{
    /** @var mixed[] */
    public array $constructorArgs = [];

    public function __construct(mixed ...$args)
    {
        $this->constructorArgs = $args;
    }
}

<?php declare(strict_types=1);

namespace jschreuder\MiddleDi;

use ReflectionMethod;

interface DiCompilerInterface
{
    public function compiledClassExists(): bool;

    public function generateCode(): string;

    public function processMethod(ReflectionMethod $method): string;

    public function compile(): static;

    public function newInstance(array ...$args): mixed;
}

<?php declare(strict_types=1);

namespace jschreuder\MiddleDi;

interface DiCompilerInterface
{
    public function compiledClassExists(): bool;

    public function generateCode(): string;

    public function compile(): void;

    public function newInstance(array ...$args): mixed;
}

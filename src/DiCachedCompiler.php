<?php declare(strict_types=1);

namespace jschreuder\MiddleDi;

use OutOfRangeException;
use ReflectionMethod;
use SplFileObject;

final class DiCachedCompiler implements DiCompilerInterface
{
    public function __construct(
        private DiCompilerInterface $compiler,
        private SplFileObject $file,
        private int $maxAge = 0
    )
    {
    }

    public function compiledClassExists(): bool
    {
        return $this->compiler->compiledClassExists();
    }

    public function compile(): void
    {
        if ($this->compiledClassExists()) {
            throw new \RuntimeException('Cannot recompile already compiled container');
        }

        if (!$this->validCache()) {
            $this->writeCacheFile($this->compiler->generateCode());
        }

        include_once $this->file->getPath();
        return;
    }

    private function writeCacheFile(string $code): void
    {
        $this->file->ftruncate(0);
        $this->file->fwrite($code);
    }

    public function generateCode(): string
    {
        return $this->compiler->generateCode();
    }

    public function processMethod(ReflectionMethod $method): string
    {
        return $this->compiler->processMethod($method);
    }

    private function validCache(): bool
    {
        if (!$this->file->isFile()) {
            return false;
        }
        if ($this->maxAge <= 0) {
            throw new OutOfRangeException('Max age must be greater then zero.');
        }
        return (time() - $this->file->getMTime()) < $this->maxAge;
    }

    public function newInstance(array ...$args): mixed
    {
        return $this->compiler->newInstance(...$args);
    }
}

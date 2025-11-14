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
        private int $maxAge = 0,
    ) {
        if ($maxAge < 0) {
            throw new OutOfRangeException("Max age must be greater then zero.");
        }
    }

    public function compiledClassExists(): bool
    {
        return $this->compiler->compiledClassExists();
    }

    public function compile(): static
    {
        if ($this->compiledClassExists()) {
            throw new DiCompilationException(
                "Cannot recompile already compiled container",
            );
        }

        if (!$this->validCache()) {
            $this->writeCacheFile($this->compiler->generateCode());
        }

        include_once $this->file->getPath();

        return $this;
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
        // Return false if there's no file cached
        if (!$this->file->isFile()) {
            return false;
        }
        // There is a file, when max-age is set to zero it will always be valid
        if ($this->maxAge === 0) {
            return true;
        }
        // Otherwise return true/false based on if it is older then allowed
        return time() - $this->file->getMTime() < $this->maxAge;
    }

    public function newInstance(array ...$args): mixed
    {
        return $this->compiler->newInstance(...$args);
    }
}

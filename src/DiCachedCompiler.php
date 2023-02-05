<?php declare(strict_types=1);

namespace jschreuder\MiddleDi;

final class DiCachedCompiler implements DiCompilerInterface
{
    public function __construct(
        private DiCompilerInterface $compiler,
        private string $location,
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

        include $this->location;
        return;
    }

    private function writeCacheFile(string $code): void
    {
        $file = fopen($this->location, 'w');
        if ($file === false) {
            throw new \RuntimeException('Could not write cachefile for compiled container.');
        }
        fwrite($file, $code);
        fclose($file);
    }

    public function generateCode(): string
    {
        return $this->compiler->generateCode();
    }

    private function validCache(): bool
    {
        if (!file_exists($this->location)) {
            return false;
        }
        if ($this->maxAge === 0) {
            return true;
        }
        return (time() - filemtime($this->location)) < $this->maxAge;
    }

    public function newInstance(array ...$args): mixed
    {
        return $this->compiler->newInstance(...$args);
    }
}

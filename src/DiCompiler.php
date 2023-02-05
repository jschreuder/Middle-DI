<?php declare(strict_types=1);

namespace jschreuder\MiddleDi;

use ReflectionClass;
use ReflectionNamedType;

final class DiCompiler implements DiCompilerInterface
{
    const COMPILED_EXTENSION = '__Compiled';

    private string $parentDi;

    public function __construct(string $parentDi)
    {
        $this->parentDi = $parentDi;
    }

    private function getCompiledName(): string
    {
        return $this->parentDi . self::COMPILED_EXTENSION;
    }

    public function compiledClassExists(): bool
    {
        return class_exists($this->getCompiledName());
    }

    public function compile(): void
    {
        if ($this->compiledClassExists()) {
            throw new \RuntimeException('Cannot recompile already compiled container');
        }

        eval(substr($this->generateCode(), 6));
    }

    public function generateCode(): string
    {
        $parent = new ReflectionClass($this->parentDi);
        return $this->generateHeader($parent)
            .$this->generateMethods($parent)
            .$this->generateFooter();
    }

    private function generateHeader(ReflectionClass $parent)
    {
        return
'<?php

namespace ' . $parent->getNamespaceName() . ';

use ' . $parent->getName() . ';

class ' . $parent->getShortName() . self::COMPILED_EXTENSION . ' extends ' . $parent->getShortName() . '
{
    private array $__services = [];

    private function __service(string $method, ?string $instanceName = null)
    {
        $suffix = is_null($instanceName) ? \'\' : \'.\' . $instanceName;
        return $this->__services[$method . $suffix] ?? ($this->__services[$method . $suffix] = parent::{$method}($instanceName));
    }

';
    }

    private function generateMethods(ReflectionClass $parent)
    {
        $code = '';

        foreach ($parent->getMethods() as $method) {
            if ($method->isPublic() && (substr($method->getName(), 0, 3) !== 'get')) {
                continue;
            }
            if (!$method->hasReturnType()) {
                throw new \RuntimeException('Service definitions must have return types');
            }
            if ($method->getNumberOfParameters() > 1) {
                throw new \RuntimeException('Service definitions cannot take more than a name parameter');
            }
            if ($method->getNumberOfParameters() === 1) {
                $parameter = $method->getParameters()[0];

                if (!is_a($parameter->getType(), ReflectionNamedType::class) || $parameter->getType()->getName() !== 'string') {
                    throw new \RuntimeException('Service factories are only allowed a single named nullable string argument.');
                }
            }

            $returnType = $method->getReturnType();
            if (!in_array($returnType, ['mixed', 'string', 'int', 'bool', 'float', 'resource', 'void', 'null', 'array', 'object'])) {
                $returnType = '\\' . $returnType;
            }

            $code .= '
    public function '.$method->getName().'(?string $instanceName = null): '.$returnType.'
    {
        return $this->__service(\'' . $method->getName() . '\', $instanceName);
    }
';
        }

        return $code;
    }

    private function generateFooter(): string
    {
        return PHP_EOL . '}' . PHP_EOL;
    }

    public function newInstance(array ...$args): mixed
    {
        $reflectedClass = new ReflectionClass($this->getCompiledName());
        return $reflectedClass->newInstance(...$args);
    }
}
